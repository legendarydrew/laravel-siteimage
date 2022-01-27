<?php
/**
 * © 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage;

use ReflectionClass;
use ReflectionException;

/**
 * ImageFormat enum.
 */
abstract class SiteImageFormat
{
    public const JPEG = 'jpg';
    public const PNG = 'png';

    /**
     * @var null
     */
    private static $constCacheArray = null;

    /**
     * Returns TRUE if the specified value name is valid for this enum.
     *
     * @param      $name
     * @param bool $strict
     *
     * @return bool
     * @throws ReflectionException
     * @static
     */
    public static function isValidName($name, bool $strict = false): bool
    {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));

        return in_array(strtolower($name), $keys);
    }

    /**
     * getConstants().
     *
     * @static
     * @return mixed
     * @throws ReflectionException
     */
    private static function getConstants()
    {
        if (self::$constCacheArray == null) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }

        return self::$constCacheArray[$calledClass];
    }

    /**
     * Returns TRUE if the specified value is valid for this enum.
     *
     * @param      $value
     * @param bool $strict
     *
     * @return bool
     * @throws ReflectionException
     * @static
     */
    public static function isValidValue($value, bool $strict = true): bool
    {
        $values = self::values();

        return in_array($value, $values, $strict);
    }

    /**
     * Returns a list of values for this enum.
     *
     * @static
     * @return array
     * @throws ReflectionException
     */
    public static function values(): array
    {
        return array_values(self::getConstants());
    }
}
