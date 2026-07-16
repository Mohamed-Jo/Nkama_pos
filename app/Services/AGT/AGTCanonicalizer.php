<?php

namespace App\Services\AGT;

class AGTCanonicalizer
{
    public function canonicalize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (! is_array($value)) {
                if (is_string($value)) {
                    $data[$key] = trim($value);
                }

                continue;
            }

            if (array_is_list($value)) {
                foreach ($value as $index => $item) {
                    if (is_array($item)) {
                        $value[$index] = $this->canonicalize($item);
                    }
                }

                $data[$key] = $value;
                continue;
            }

            $data[$key] = $this->canonicalize($value);
        }

        return $data;
    }
}
