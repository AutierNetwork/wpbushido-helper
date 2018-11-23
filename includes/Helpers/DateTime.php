<?php
/**
 * WPBushido Plugin
 *
 * @package WPBushido
 */

namespace WPBushido\Helpers;

class DateTime
{
    /**
     * Transform a 24 hours floatval to a HH:MM string
     * @param  float $value 24 hours floatval
     * @param  string $delimiter Delimiter for hours and minutes
     * @return string            HH:MM string
     */
    public static function floatToTime($value, $delimiter = ':', $noRoot = false)
    {
        $hours = intval($value);

        $remainder = $value - $hours;
        $minutes = round(60 * $remainder);

        if ($noRoot) {
            $minutes = ($minutes == 0) ? '' : str_pad($minutes, 2, 0, STR_PAD_LEFT);
            return $hours . $delimiter . $minutes;
        }

        return str_pad($hours, 2, 0, STR_PAD_LEFT) . $delimiter . str_pad($minutes, 2, 0, STR_PAD_LEFT);
    }

    /**
     * Validate for user with at least 18 years old
     *
     * @param string $birthDate
     * @return void
     */
    public static function validateAge($birthDate, $age)
    {
        if ($age < 1) {
            return true;
        }

        $birthDate = new \DateTime($birthDate);
        $now = new \DateTime();
        if ($birthDate->diff($now)->y < $age){
            return false;
        } else {
            return true;
        }
    }
}
