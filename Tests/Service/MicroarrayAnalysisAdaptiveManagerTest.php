<?php

namespace Tests\MinitoolsBundle\Service;

use Amelaye\BioPHP\Domain\Tools\Service\MathematicsFunctions;
use Amelaye\BioTools\Service\MicroarrayAnalysisAdaptiveManager;
use PHPUnit\Framework\TestCase;

class MicroarrayAnalysisAdaptiveManagerTest extends TestCase
{
    protected $mathematicsManager;

    public function setUp(): void
    {
        $this->mathematicsManager = new MathematicsFunctions();
    }

    /**
     * Legacy's own default textarea content (microarray_analysis_adaptive_quantification.php),
     * single tab per column as the real minitool produces - not the double-tab fixture the
     * previous version of this test used, which was accidentally shaped to dodge a parsing
     * bug (fileToArray() was searching for "1\t\t1\t" instead of "1\t1\t").
     */
    public function testProcessMicroarrayDataAdaptiveQuantificationMethod()
    {
        $file = "Column\tRow\tName\tF532 Median\tB532 Median\tF635 Median\tB635 Median\n"
            . "1\t1\tControl -\t1145\t160\t1182\t122\n"
            . "2\t1\tControl -\t593\t218\t515\t122\n"
            . "3\t1\tControl -\t1257\t183\t1382\t128\n"
            . "4\t1\tControl -\t525\t168\t475\t126\n"
            . "5\t1\tControl -\t1132\t155\t1271\t120\n"
            . "6\t1\tControl -\t610\t218\t510\t122\n"
            . "7\t1\tControl -\t1099\t176\t1292\t127\n"
            . "8\t1\tControl -\t603\t180\t481\t123\n"
            . "9\t1\tControl -\t878\t149\t1082\t119\n"
            . "10\t1\tControl -\t441\t139\t444\t119\n"
            . "1\t2\tControl +\t10387\t140\t4269\t116\n"
            . "2\t2\tControl +\t9035\t132\t3705\t115\n"
            . "3\t2\tControl +\t7899\t126\t3331\t117\n"
            . "4\t2\tControl +\t7039\t118\t2883\t114\n"
            . "5\t2\tControl +\t9407\t138\t3994\t115\n"
            . "6\t2\tControl +\t7545\t127\t3240\t116\n"
            . "7\t2\tControl +\t8915\t134\t3843\t114\n"
            . "8\t2\tControl +\t7169\t126\t3038\t119\n"
            . "9\t2\tControl +\t7867\t137\t3345\t120\n"
            . "10\t2\tControl +\t9369\t140\t4184\t122\n"
            . "1\t3\tGene 1\t4276\t111\t574\t108\n"
            . "2\t3\tGene 1\t3798\t111\t439\t107\n"
            . "3\t3\tGene 1\t3311\t110\t418\t107\n"
            . "4\t3\tGene 1\t4258\t109\t441\t106\n"
            . "5\t3\tGene 1\t3548\t109\t445\t104\n"
            . "6\t3\tGene 1\t3448\t108\t424\t101\n"
            . "7\t3\tGene 1\t3412\t107\t415\t105\n"
            . "8\t3\tGene 1\t3856\t106\t445\t107\n"
            . "9\t3\tGene 1\t3510\t116\t395\t111\n"
            . "10\t3\tGene 1\t3853\t108\t427\t109\n"
            . "1\t4\tGene 2\t4830\t119\t670\t107\n"
            . "2\t4\tGene 2\t5625\t101\t804\t103\n"
            . "3\t4\tGene 2\t5053\t118\t682\t105\n"
            . "4\t4\tGene 2\t5895\t106\t835\t105\n"
            . "5\t4\tGene 2\t5913\t102\t816\t105\n"
            . "6\t4\tGene 2\t5041\t103\t773\t103\n"
            . "7\t4\tGene 2\t4846\t114\t703\t106\n"
            . "8\t4\tGene 2\t5362\t107\t812\t108\n"
            . "9\t4\tGene 2\t4811\t117\t716\t104\n"
            . "10\t4\tGene 2\t4610\t109\t627\t108\n"
            . "1\t5\tGene 3\t431\t99\t427\t107\n"
            . "2\t5\tGene 3\t536\t90\t520\t105\n"
            . "3\t5\tGene 3\t528\t100\t475\t109\n"
            . "4\t5\tGene 3\t489\t92\t504\t105\n"
            . "5\t5\tGene 3\t509\t93\t508\t108\n"
            . "6\t5\tGene 3\t486\t92\t523\t107\n"
            . "7\t5\tGene 3\t605\t104\t574\t111\n"
            . "8\t5\tGene 3\t562\t97\t638\t109\n"
            . "9\t5\tGene 3\t591\t108\t577\t112\n"
            . "10\t5\tGene 3\t609\t101\t626\t110\n"
            . "1\t6\tGene 4\t30728\t202\t3353\t130\n"
            . "2\t6\tGene 4\t41199\t206\t4245\t131\n"
            . "3\t6\tGene 4\t22218\t206\t2434\t128\n"
            . "4\t6\tGene 4\t30179\t199\t3062\t122\n"
            . "5\t6\tGene 4\t26642\t170\t2525\t122\n"
            . "6\t6\tGene 4\t23061\t184\t2259\t119\n"
            . "7\t6\tGene 4\t29017\t183\t2782\t114\n"
            . "8\t6\tGene 4\t27071\t176\t2747\t116\n"
            . "9\t6\tGene 4\t22631\t164\t2345\t110\n"
            . "10\t6\tGene 4\t23668\t191\t2559\t113\n"
            . "1\t7\tGene 5\t3190\t103\t2135\t104\n"
            . "2\t7\tGene 5\t3294\t106\t2389\t100\n"
            . "3\t7\tGene 5\t2114\t106\t1769\t107\n"
            . "4\t7\tGene 5\t3029\t103\t2524\t105\n"
            . "5\t7\tGene 5\t3236\t105\t2237\t103\n"
            . "6\t7\tGene 5\t3296\t106\t2379\t104\n"
            . "7\t7\tGene 5\t3131\t117\t2416\t110\n"
            . "8\t7\tGene 5\t3261\t112\t2440\t108\n"
            . "9\t7\tGene 5\t2866\t111\t2469\t107\n"
            . "10\t7\tGene 5\t2621\t116\t2057\t111\n"
            . "1\t8\tGene 6\t3791\t111\t3077\t109\n"
            . "2\t8\tGene 6\t4054\t112\t3210\t107\n"
            . "3\t8\tGene 6\t3235\t115\t2362\t112\n"
            . "4\t8\tGene 6\t3874\t117\t3070\t105\n"
            . "5\t8\tGene 6\t4208\t101\t3399\t109\n"
            . "6\t8\tGene 6\t3283\t117\t2779\t108\n"
            . "7\t8\tGene 6\t3354\t109\t2403\t105\n"
            . "8\t8\tGene 6\t4139\t104\t3307\t108\n"
            . "9\t8\tGene 6\t2706\t108\t2046\t108\n"
            . "10\t8\tGene 6\t3027\t101\t2693\t105\n"
            . "1\t9\tGene 7\t979\t98\t805\t108\n"
            . "2\t9\tGene 7\t877\t95\t766\t106\n"
            . "3\t9\tGene 7\t877\t98\t798\t110\n"
            . "4\t9\tGene 7\t932\t94\t791\t105\n"
            . "5\t9\tGene 7\t941\t95\t873\t111\n"
            . "6\t9\tGene 7\t995\t96\t943\t110\n"
            . "7\t9\tGene 7\t967\t109\t861\t112\n"
            . "8\t9\tGene 7\t1073\t101\t926\t109\n"
            . "9\t9\tGene 7\t984\t109\t893\t111\n"
            . "10\t9\tGene 7\t976\t106\t901\t110\n"
            . "1\t10\tGene 8\t215\t194\t152\t124\n"
            . "2\t10\tGene 8\t212\t187\t126\t120\n"
            . "3\t10\tGene 8\t276\t201\t177\t131\n"
            . "4\t10\tGene 8\t205\t188\t139\t124\n"
            . "5\t10\tGene 8\t227\t186\t137\t121\n"
            . "6\t10\tGene 8\t223\t209\t137\t123\n"
            . "7\t10\tGene 8\t214\t182\t139\t116\n"
            . "8\t10\tGene 8\t189\t168\t129\t114\n"
            . "9\t10\tGene 8\t217\t167\t163\t115\n"
            . "10\t10\tGene 8\t247\t179\t156\t118\n";
        $aExpected = [
            'Control +' => [
                'n_data' => 10,
                'median1' => 0.6370333645022157,
                'medlog1' => -0.196,
                'median2' => 1.56979367586736,
                'medlog2' => 0.196,
            ],
            'Control -' => [
                'n_data' => 10,
                'median1' => 0.24615014241980115,
                'medlog1' => -0.609,
                'median2' => 4.0625611272469655,
                'medlog2' => 0.609,
            ],
            'Gene 1' => [
                'n_data' => 10,
                'median1' => 2.8815188911973766,
                'medlog1' => 0.46,
                'median2' => 0.347176961738714,
                'medlog2' => -0.459,
            ],
            'Gene 2' => [
                'n_data' => 10,
                'median1' => 2.100142083714854,
                'medlog1' => 0.322,
                'median2' => 0.4761582877493338,
                'medlog2' => -0.322,
            ],
            'Gene 3' => [
                'n_data' => 10,
                'median1' => 0.2749883740344782,
                'medlog1' => -0.561,
                'median2' => 3.636518609114292,
                'medlog2' => 0.561,
            ],
            'Gene 4' => [
                'n_data' => 10,
                'median1' => 2.68200862028545,
                'medlog1' => 0.428,
                'median2' => 0.3728739796226266,
                'medlog2' => -0.428,
            ],
            'Gene 5' => [
                'n_data' => 10,
                'median1' => 0.3519612541247476,
                'medlog1' => -0.454,
                'median2' => 2.841977016652843,
                'medlog2' => 0.454,
            ],
            'Gene 6' => [
                'n_data' => 10,
                'median1' => 0.3348861236974843,
                'medlog1' => -0.475,
                'median2' => 2.9861053160714732,
                'medlog2' => 0.475,
            ],
            'Gene 7' => [
                'n_data' => 10,
                'median1' => 0.30168778156765946,
                'medlog1' => -0.52,
                'median2' => 3.3147974522309953,
                'medlog2' => 0.52,
            ],
            'Gene 8' => [
                'n_data' => 10,
                'median1' => 0.36970073513872237,
                'medlog1' => -0.432,
                'median2' => 2.7049167337839117,
                'medlog2' => 0.432,
            ],
        ];


        $aExpected = [
            'Control +' => [
                'n_data' => 10,
                'median1' => 0.6370333645022157,
                'medlog1' => -0.196,
                'median2' => 1.56979367586736,
                'medlog2' => 0.196,
            ],
            'Control -' => [
                'n_data' => 10,
                'median1' => 0.24615014241980115,
                'medlog1' => -0.609,
                'median2' => 4.0625611272469655,
                'medlog2' => 0.609,
            ],
            'Gene 1' => [
                'n_data' => 10,
                'median1' => 2.8815188911973766,
                'medlog1' => 0.46,
                'median2' => 0.347176961738714,
                'medlog2' => -0.459,
            ],
            'Gene 2' => [
                'n_data' => 10,
                'median1' => 2.100142083714854,
                'medlog1' => 0.322,
                'median2' => 0.4761582877493338,
                'medlog2' => -0.322,
            ],
            'Gene 3' => [
                'n_data' => 10,
                'median1' => 0.2749883740344782,
                'medlog1' => -0.561,
                'median2' => 3.636518609114292,
                'medlog2' => 0.561,
            ],
            'Gene 4' => [
                'n_data' => 10,
                'median1' => 2.68200862028545,
                'medlog1' => 0.428,
                'median2' => 0.3728739796226266,
                'medlog2' => -0.428,
            ],
            'Gene 5' => [
                'n_data' => 10,
                'median1' => 0.3519612541247476,
                'medlog1' => -0.454,
                'median2' => 2.841977016652843,
                'medlog2' => 0.454,
            ],
            'Gene 6' => [
                'n_data' => 10,
                'median1' => 0.3348861236974843,
                'medlog1' => -0.475,
                'median2' => 2.9861053160714732,
                'medlog2' => 0.475,
            ],
            'Gene 7' => [
                'n_data' => 10,
                'median1' => 0.30168778156765946,
                'medlog1' => -0.52,
                'median2' => 3.3147974522309953,
                'medlog2' => 0.52,
            ],
            'Gene 8' => [
                'n_data' => 10,
                'median1' => 0.36970073513872237,
                'medlog1' => -0.432,
                'median2' => 2.7049167337839117,
                'medlog2' => 0.432,
            ],
        ];

        $service = new MicroarrayAnalysisAdaptiveManager($this->mathematicsManager);
        $testFunction = $service->processMicroarrayDataAdaptiveQuantificationMethod($file);

        $this->assertEqualsWithDelta($testFunction, $aExpected, 0.0001);
    }

    /**
     * A weak/failed spot, where the background reading is greater than or equal to the raw
     * signal, is ordinary (non-adversarial) microarray data - not a contrived edge case. Before
     * the background-corrected intensity was floored to a small positive epsilon, such a spot
     * produced a zero/negative value that silently turned into NAN/INF/-INF through the
     * ratio and log10() computations, poisoning that gene's results with no diagnostic.
     */
    public function testProcessMicroarrayDataAdaptiveQuantificationMethodWithWeakSpot()
    {
        // GeneA is a normal, well-behaved spot. GeneWeak's background (250) exceeds its raw
        // signal (100) on channel 1 - a real, non-adversarial failure mode for a weak spot,
        // not a contrived input. Before the background-corrected intensity was floored to a
        // small positive epsilon, this produced a negative channel-1/channel-2 ratio and
        // log10() of that ratio silently returned NAN.
        $file = "Column\tRow\tName\tF532 Median\tB532 Median\tF635 Median\tB635 Median\n"
            . "1\t1\tGeneA\t1000\t50\t500\t60\n"
            . "2\t1\tGeneWeak\t100\t250\t200\t80\n";

        $service = new MicroarrayAnalysisAdaptiveManager($this->mathematicsManager);
        $testFunction = $service->processMicroarrayDataAdaptiveQuantificationMethod($file);

        $this->assertArrayHasKey('GeneWeak', $testFunction);
        foreach (['median1', 'medlog1', 'median2', 'medlog2'] as $sKey) {
            $this->assertIsFloat($testFunction['GeneWeak'][$sKey]);
            $this->assertTrue(
                is_finite($testFunction['GeneWeak'][$sKey]),
                "$sKey must be finite, got " . var_export($testFunction['GeneWeak'][$sKey], true)
            );
        }
    }

    public function testProcessMicroarrayDataAdaptiveQuantificationMethodException()
    {
        $this->expectException(\Exception::class);
        $file = [];

        $service = new MicroarrayAnalysisAdaptiveManager($this->mathematicsManager);
        $service->processMicroarrayDataAdaptiveQuantificationMethod($file);
    }
}