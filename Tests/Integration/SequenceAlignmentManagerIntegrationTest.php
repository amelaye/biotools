<?php
/**
 * Integration test for SequenceAlignmentManager: wires the real Pam250MatrixDigitApi,
 * stubbing only its HTTP transport with amelaye/biophp's own real PAM250 substitution
 * matrix fixture - unlike Tests/Service/SequenceAlignmentManagerTest.php, which mocks
 * Pam250MatrixDigitApiAdapter directly.
 */
namespace Tests\Integration;

use Amelaye\BioPHP\Api\Pam250MatrixDigitApi;
use Amelaye\BioTools\Service\SequenceAlignmentManager;
use PHPUnit\Framework\TestCase;

class SequenceAlignmentManagerIntegrationTest extends TestCase
{
    use RealBioPhpApiTrait;

    public function testRealPam250MatrixScoresProteinAlignment()
    {
        $pam250Data = $this->loadBioPhpSample('Pam250Matrix.php', 'aPam250Matrix');

        $oManager = new SequenceAlignmentManager(
            $this->buildRealListApi(Pam250MatrixDigitApi::class, $pam250Data)
        );

        $aMatrix = [];
        $mj = $mi = 0;
        $oManager->step1Protein($aMatrix, $mj, $mi, 2, 2, ["A", "A"], ["A", "A"]);

        // real PAM250 "AA" substitution score is 2 - cumulative match brings [1][1] to 4
        $this->assertEquals([[2, 2], [2, 4]], $aMatrix);
    }
}
