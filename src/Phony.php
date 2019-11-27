<?php

namespace Zipavlin;

class Phony
{
    private $raw;
    private $normalized;
    private $parsed;
    private $mobileOperatorNumbers = [
        '030',
        '040',
        '050',
        '031',
        '041',
        '051',
        '059',
        '064',
        '065',
        '068',
        '069',
        '070'
    ];

    // PRIVATE METHODS
    /**
     * Check if value is set and not empty
     * @param string|null $value
     * @return bool
     */
    private function blank(string $value = null)
    {
        return !isset($value) or empty($value) or $value == null;
    }

    /**
     * Remove redundant characters from string, bringing it to a 9 number format
     * @param string $input
     * @return string|string[]|null
     */
    private function normalize() {
        if (!$this->raw) return new \Exception("Input not set. Did you ran 'from' method?");
        return $this->normalized ?? $this->normalized = ltrim(preg_replace_callback("/^(?:(?:(?:\+386|00386|386)\s*(?:\(0\))?(\d{1,2})\s*([\d ]{7,}))|(?:(\d{2,3})(?:[\s\/])([\d -]+)))$/", function ($matches) {
            if (!$this->blank($matches[1]) and !$this->blank($matches[2])) {
                return '0'.$matches[1].trim(str_replace([' ', '-'], '', $matches[2]));
            }
            else if (!$this->blank($matches[3]) and !$this->blank($matches[4])) {
                return $matches[3].trim(str_replace([' ', '-'], '', $matches[4]));
            }
            else {
                return null;
            }
        }, $this->raw), '0');
    }

    /**
     * Check if this is mobile number
     * @param string $input
     * @return bool
     */
    private function mobile()
    {
        return in_array('0'.substr($this->normalize(), 0, 2), $this->mobileOperatorNumbers);
    }

    /**
     * Parse number to area and number
     * @param $input
     * @return array|object
     */
    private function parse()
    {
        if (!$this->parsed) {
            if ($this->mobile()) {
                preg_match("/^(" . implode("|", $this->mobileOperatorNumbers) . ")(\d{6})$/", $this->normalize(), $matches);
                return (Object)[
                    'type' => 'mobile',
                    'area' => $matches[1],
                    'number' => $matches[2]
                ];
            } else {
                preg_match("/^(\d)(\d{7})$/", $this->normalize(), $matches);
                return (Object)[
                    'type' => 'static',
                    'area' => $matches[1],
                    'number' => $matches[2]
                ];
            }
        } else {
            return $this->parsed;
        }
    }

    /**
     * Get array of tokens from $format sting
     * @param string $format
     * @return array
     */
    private function tokens(string $format)
    {
        preg_match_all("/(?:{(area|number)(?::(\d))?})/", $format, $matches);
        if (!count($matches)) return [];
        $parsed = $this->parse();
        $tokens = [];
        $index = [
            'area' => 0,
            'number' => 0
        ];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $type = $matches[1][$i];
            $count = $this->blank($matches[2][$i]) ? strlen($parsed->{$type}) : $matches[2][$i];
            $start = $index[$type];
            array_push($tokens, (Object) [
                'raw' => $matches[0][$i],
                'type' => $type,
                'count' => $count,
                'start' => $start,
                'string' => substr($parsed->{$type}, $start, $count)
            ]);
            $index[$type] += $count;
        }

        return $tokens;
    }

    /**
     * Get $format string replacements
     * @param string $format
     * @return array
     */
    private function replacements(string $format)
    {
        return array_map(function ($token) {
            return $token->string;
        }, $this->tokens($format));
    }

    /**
     * Helper function to replace tokens with items from array
     * @param $pattern
     * @param array $replacements
     * @param $subject
     * @return string|string[]|null
     */
    private function preg_replace_array($pattern, array $replacements, $subject)
    {
        return preg_replace_callback($pattern, function () use (&$replacements) {
            foreach ($replacements as $key => $value) {
                return array_shift($replacements);
            }
        }, $subject);
    }

    // PUBLIC METHODS
    /**
     * Create new instance from unformatted phone number string
     * @param string $input
     * @return Phony
     */
    public function from(string $input): Phony
    {
        $this->raw = $input;
        return $this;
    }

    /**
     * Return phone number as parsed object
     * @return array|object
     */
    public function toParsed()
    {
        return $this->parse();
    }

    /**
     * Return phone number as short normalized string
     * @return string
     */
    public function toNormal(): string
    {
        return '0'.$this->normalize();
    }

    /**
     * Return phone number as href link
     * @return string
     */
    public function toHref(): string
    {
        return 'tel:'.$this->toFull();
    }

    /**
     * Return phone number as full trimmed string
     * @return string
     */
    public function toFull(): string
    {
        return '00386'.$this->normalize();
    }

    /**
     * Return phone number as fully formatted string
     * @return string
     */
    public function toDisplay(): string
    {
        return "+386 (0)".($this->mobile() ? preg_replace("/(\d{2})(\d{3})(\d{3})/", "$1 $2 $3", $this->normalize()) : preg_replace("/(\d)(\d{2})(\d{2})(\d{3})/", "$1 $2 $3 $4", $this->normalize()));
    }

    /**
     * Return phone number formatted with $format string
     * @param null|string|array $format
     * if format is array, first item is mobile format, second is stationary format
     * use {area} for area code
     * use {number} or {number:2} for number (2 numbers)
     * example: +386 (0){area} {number:3} {number:4}
     * @return string
     */
    public function toFormat($format = null): string
    {
        if (!$format) return $this->toDisplay();

        $parsed = $this->parse();
        if (is_array($format) and count($format) == 2) {
            return $this->preg_replace_array("/{(?:area|number)(?::\d)?}/", $this->replacements($format[$parsed->type != 'mobile']), $format);
        }
        elseif (is_array($format)) {
            return $this->preg_replace_array("/{(?:area|number)(?::\d)?}/", $this->replacements($format[0]), $format);
        }
        else {
            return $this->preg_replace_array("/{(?:area|number)(?::\d)?}/", $this->replacements($format), $format);
        }
    }

    /**
     * Return object with all phone number info
     * @param null|string|array $format
     * @return object
     */
    public function toObject($format = null)
    {
        return (Object) [
            'raw' => $this->raw,
            'normal' => $this->normalize(),
            'short' => $this->toNormal(),
            'full' => $this->toFull(),
            'href' => $this->toHref(),
            'display' => $this->toFormat($format)
        ];
    }

    /**
     * Renders a attribute
     * @param string|null $attributes
     * @param null|array|string $format
     */
    public function toRender(string $attributes = null, $format = null)
    { ?>
        <a href="<?php $this->toHref(); ?>"<?php ($attributes ? ' '.$attributes : ''); ?>><?php $this->toFormat($format); ?></a>
    <?php }
}
