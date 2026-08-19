<?php

declare(strict_types=1);

namespace EcomHouse\FreeShippingGoalBar\Setup\Patch\Data;

use EcomHouse\FreeShippingGoalBar\Model\Config;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Carries settings over from the predecessor module, `ecomhouse/freeshippingprogressbar`.
 *
 * A store replacing that module keeps its threshold and stays switched on instead of silently
 * losing the bar until someone notices and reconfigures it. Legacy rows are read, not deleted -
 * the old module may still be installed during a staged migration, and an uninstall removes its
 * own rows anyway.
 */
class MigrateLegacyConfig implements DataPatchInterface
{
    private const LEGACY_ENABLED = 'checkout/cart/freeshipping_progress_enable';
    private const LEGACY_USE_METHOD_CONFIG = 'checkout/cart/use_freeshipping_method_config';
    private const LEGACY_MIN_TOTAL = 'checkout/cart/freeshipping_progress_min_total';

    private const PATH_ENABLED = 'ecomhouse_freeshipping_goal_bar/general/enabled';
    private const PATH_THRESHOLD_SOURCE = 'ecomhouse_freeshipping_goal_bar/general/threshold_source';
    private const PATH_MANUAL_THRESHOLD = 'ecomhouse_freeshipping_goal_bar/general/manual_threshold';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly WriterInterface $configWriter,
    ) {
    }

    /**
     * Copy every legacy row onto its new path, preserving scope.
     *
     * @return $this
     */
    public function apply()
    {
        foreach ($this->getLegacyRows() as $row) {
            $this->migrate($row['path'], (string) $row['value'], $row['scope'], (int) $row['scope_id']);
        }

        return $this;
    }

    /**
     * Legacy configuration rows written by the predecessor module.
     *
     * @return array<int, array{path: string, value: string|null, scope: string, scope_id: int|string}>
     */
    private function getLegacyRows(): array
    {
        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('core_config_data');

        $select = $connection->select()
            ->from($table, ['path', 'value', 'scope', 'scope_id'])
            ->where('path IN (?)', [self::LEGACY_ENABLED, self::LEGACY_USE_METHOD_CONFIG, self::LEGACY_MIN_TOTAL]);

        return $connection->fetchAll($select);
    }

    /**
     * Write one legacy value onto the matching new path.
     *
     * @param string $legacyPath
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     * @return void
     */
    private function migrate(string $legacyPath, string $value, string $scope, int $scopeId): void
    {
        $target = match ($legacyPath) {
            self::LEGACY_ENABLED => [self::PATH_ENABLED, $value],
            self::LEGACY_USE_METHOD_CONFIG => [
                self::PATH_THRESHOLD_SOURCE,
                $value === '1' ? Config::SOURCE_CARRIER : Config::SOURCE_MANUAL,
            ],
            self::LEGACY_MIN_TOTAL => [self::PATH_MANUAL_THRESHOLD, $value],
            default => null,
        };

        if ($target === null) {
            return;
        }

        $this->configWriter->save($target[0], $target[1], $scope, $scopeId);
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
