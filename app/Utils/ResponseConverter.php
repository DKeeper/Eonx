<?php
declare(strict_types=1);

namespace App\Utils;

class ResponseConverter
{
    /**
     * @param array|\stdClass $response
     *
     * @return array
     */
    public static function prepareResponse($response): array
    {
        if ($response instanceof \stdClass) {
            $response = \get_object_vars($response);
        }

        return \array_map(static function ($item) {
            if ($item instanceof \stdClass) {
                $item = \get_object_vars($item);
            }

            if (\is_array($item)) {
                $item = self::prepareResponse($item);
            }

            return $item;
        }, $response);
    }
}
