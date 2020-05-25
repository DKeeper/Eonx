<?php
declare(strict_types=1);

namespace Unit\Utils;

use App\Utils\ResponseConverter;
use PHPUnit\Framework\TestCase;

class ResponseConverterTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     *
     * @param $response
     * @param array $expected
     */
    public function testConverter($response, array $expected)
    {
        $this->assertEquals($expected, ResponseConverter::prepareResponse($response));
    }

    /**
     * @return array
     */
    public function dataProvider(): array
    {
        $expected = [
            'test',
            'test2',
            'test3' => [
                'test4',
                'test5',
                'test6',
                'test7' => [
                    'test8',
                    'test9',
                    'test10',
                ],
            ],
        ];
        $item1 = $expected;
        $item1['test3'] = (object) $item1['test3'];
        $item2 = \json_decode(\json_encode($expected), false);

        return [
            [
                $item1, $expected,
            ],
            [
                $item2, $expected,
            ],
        ];
    }
}
