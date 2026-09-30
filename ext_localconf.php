<?php

declare(strict_types=1);

use FGTCLB\CategoryTypes\Routing\Aspect\CategoryFilterMapper;

(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['CategoryFilterMapper'] = CategoryFilterMapper::class;
})();
