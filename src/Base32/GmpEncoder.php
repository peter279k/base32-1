<?php

/*
 * This file is part of the Base32 package
 *
 * Copyright (c) 2017 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/base32
 *
 */

namespace Tuupola\Base32;

use Tuupola\Base32;

class GmpEncoder
{
    private $options = [
        "characters" => Base32::RFC4648,
        "padding" => "=",
    ];

    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, (array) $options);
    }

    public function encode($data)
    {
        $data = trim($data);
        if (empty($data)) {
            return "";
        }

        /* Create binary string zeropadded to eight bits. */
        $binary = gmp_strval(gmp_import($data), 2);
        $binary = str_pad($binary, strlen($data) * 8, "0", STR_PAD_LEFT);

        /* Split to five bit chunks and make sure last chunk has five bits. */
        $binary = str_split($binary, 5);
        $last = array_pop($binary);
        $binary[] = str_pad($last, 5, "0", STR_PAD_RIGHT);

        /* Convert each five bits to a Base32 character. */
        $encoded = implode("", array_map(function ($fivebits) {
            $index = bindec($fivebits);
            return $this->options["characters"][$index];
        }, $binary));

        /* Pad to eight characters when requested. */
        if (!empty($this->options["padding"])) {
            if ($modulus = strlen($encoded) % 8) {
                $padding = 8 - $modulus;
                $encoded .= str_repeat($this->options["padding"], $padding);
            }
        }

        return $encoded;
    }

    public function decode($data)
    {
        $data = trim($data);
        if (empty($data)) {
            return "";
        }

        $data = str_split($data);
        $data = array_map(function ($character) {
            if ($character !== $this->options["padding"]) {
                $index = strpos($this->options["characters"], $character);
                return sprintf("%05b", $index);
            }
        }, $data);
        $binary = implode("", $data);

        /* Split to eight bit chunks. */
        $data = str_split($binary, 8);

        /* Make sure binary is divisible by eight by ignoring the incomplete byte. */
        $last = array_pop($data);
        if (8 === strlen($last)) {
            $data[] = $last;
        }

        return implode("", array_map(function ($byte) {
            //return pack("C", bindec($byte));
            return chr(bindec($byte));
        }, $data));
    }
}
