<?php

namespace Stillat\Meerkat\Core\Validators;

use Stillat\Meerkat\Core\Logging\LocalErrorCodeRepository;
use Stillat\Meerkat\Core\ValidationResult;

/**
 * Class PathPrivilegeValidator
 *
 * Provides utilities for testing paths for required permissions.
 *
 * @package Stillat\Meerkat\Core\Validators
 * @since 2.0.0
 */
class PathPrivilegeValidator
{

    /**
     * The default directory permissions to use.
     */
    const DEFAULT_DIR_PERMISSIONS = 644;

    /**
     * The key used to identify if a path can be used.
     */
    const RESULT_CAN_USE_DIRECTORY = 'can_use_directory';

    /**
     * The key used to identify validation results.
     */
    const RESULT_VALIDATION_RESULTS = 'validation_results';

    /**
     * Tests if the given path has the required permissions.
     *
     * @param string $path The path to test.
     * @param string $permissionDeniedErrorCode The error code to use on failure.
     * @return array
     */
    public static function validatePathPermissions($path, $permissionDeniedErrorCode)
    {
        $canUseDirectory = false;
        $validationResults = new ValidationResult();

        if (file_exists($path) == false || is_dir($path) == false) {
            $wasSuccess = mkdir($path, self::DEFAULT_DIR_PERMISSIONS, true);

            if ($wasSuccess) {
                $canUseDirectory = true;
            }
        } else {
            // Can Meerkat write to this directory? Let us check.
            $canWrite = is_writeable($path);

            if ($canWrite == false) {
                // Attempt to adjust Meerkat's permissions over this directory.
                try {
                    $couldAdjust = chmod($path, self::DEFAULT_DIR_PERMISSIONS);

                    if ($couldAdjust && is_writeable($path)) {
                        $canUseDirectory = true;
                    }
                } catch (\ErrorException $e) {
                    // Permission denied.
                    $canUseDirectory = false;
                    $validationResults->reasons[] = [
                        'code' => $permissionDeniedErrorCode,
                        'msg' => 'Insufficient privileges for directory: '.$path
                    ];

                    LocalErrorCodeRepository::logCodeMessage($permissionDeniedErrorCode, 'Insufficient privileges for directory: '.$path);
                }
            } else {
                $canUseDirectory = true;
            }
        }

        $validationResults->updateValidity();

        return [
          self::RESULT_CAN_USE_DIRECTORY => $canUseDirectory,
          self::RESULT_VALIDATION_RESULTS => $validationResults
        ];
    }

}