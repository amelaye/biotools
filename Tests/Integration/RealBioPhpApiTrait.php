<?php
/**
 * Wires the real (non-mocked) BioPHP Api classes for integration tests. Only the
 * outermost HTTP transport is stubbed (via Guzzle's MockHandler) with the real
 * production fixtures shipped by amelaye/biophp itself (vendor/amelaye/biophp/Tests/Api/samples) -
 * so the Api class's own JSON/DTO mapping code actually runs, unlike the Service-level
 * unit tests which mock the Adapter interfaces directly and never exercise that code path.
 */
namespace Tests\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use JMS\Serializer\SerializerBuilder;
use ReflectionClass;

trait RealBioPhpApiTrait
{
    /**
     * Loads a sample fixture file from amelaye/biophp's own test suite and returns the
     * array of DTOs it builds.
     * @param   string  $sSampleFile    e.g. "Vendors.php"
     * @param   string  $sVariableName  the variable the sample script assigns its DTOs to
     * @return  array
     */
    private function loadBioPhpSample(string $sSampleFile, string $sVariableName): array
    {
        $sSamplePath = dirname(__DIR__, 2) . '/vendor/amelaye/biophp/Tests/Api/samples/' . $sSampleFile;
        require $sSamplePath;
        return $$sVariableName;
    }

    /**
     * Builds a real BioPHP Api instance (e.g. VendorApi, AminoApi...) whose HTTP layer
     * is stubbed to return the given DTOs as a Hydra "member" collection, exactly as the
     * live API would - so the Api class's own field-by-field DTO hydration runs for real.
     * @param   string  $sApiClass      Fully qualified Amelaye\BioPHP\Api\* class name
     * @param   array   $aDtoObjects
     * @param   int     $iMaxCalls      Several Service constructors call the same
     * getter twice (once per static "GetXxxArray" helper) - the mock queue is refilled
     * with this many identical responses to survive that.
     * @return  object
     */
    private function buildRealListApi(string $sApiClass, array $aDtoObjects, int $iMaxCalls = 5): object
    {
        $aMembers = [];
        foreach ($aDtoObjects as $oDto) {
            $aMembers[] = $this->dtoToHydraMember($oDto);
        }

        return $this->buildRealApi($sApiClass, ['hydra:member' => $aMembers], $iMaxCalls);
    }

    /**
     * Builds a real BioPHP Api instance whose HTTP layer returns a single JSON object
     * (not a Hydra collection) - used by PKApi::getPkValueById().
     * @param   string  $sApiClass
     * @param   array   $aResponseBody
     * @param   int     $iMaxCalls
     * @return  object
     */
    private function buildRealApi(string $sApiClass, array $aResponseBody, int $iMaxCalls = 5): object
    {
        $sJson = json_encode($aResponseBody);
        // array_fill() would copy the same Response instance into every slot, and its
        // underlying stream is exhausted after the first read - so a fresh object per slot.
        $aResponses = [];
        for ($i = 0; $i < $iMaxCalls; $i++) {
            $aResponses[] = new Response(200, [], $sJson);
        }

        $oMockHandler = new MockHandler($aResponses);
        $oClient = new Client([
            'base_uri' => 'http://api.amelayes-biophp.net',
            'handler' => HandlerStack::create($oMockHandler),
        ]);
        $oSerializer = SerializerBuilder::create()->build();

        return new $sApiClass($oClient, $oSerializer);
    }

    /**
     * Reflects over a DTO's public getters (no-arg) to rebuild the camelCase associative
     * array the real Api classes expect from the JSON payload - since every Api class
     * reads $elem["propertyName"] using exactly that convention.
     * @param   object  $oDto
     * @return  array
     */
    private function dtoToHydraMember(object $oDto): array
    {
        $aMember = [];
        $oReflection = new ReflectionClass($oDto);
        foreach ($oReflection->getMethods() as $oMethod) {
            if (
                str_starts_with($oMethod->getName(), 'get')
                && $oMethod->getNumberOfRequiredParameters() === 0
                && $oMethod->isPublic()
            ) {
                $sProperty = lcfirst(substr($oMethod->getName(), 3));
                try {
                    // an unset typed property (e.g. an optional field the fixture never
                    // called the setter for) throws on read - skip it, exactly as the
                    // real Api classes' own isset() guards do for optional JSON fields
                    $aMember[$sProperty] = $oMethod->invoke($oDto);
                } catch (\Throwable $oException) {
                    continue;
                }
            }
        }
        return $aMember;
    }
}
