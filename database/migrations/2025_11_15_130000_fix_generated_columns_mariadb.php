<?php declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Domains\CoreApp\Migration\MigrationAbstract;

return new class extends MigrationAbstract {
    /**
     * @return void
     */
    public function up(): void
    {
        // Helper to drop columns if they exist
        $dropColumnsIfExist = function (string $table, array $columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    Schema::table($table, function (Blueprint $table) use ($column) {
                        $table->dropColumn($column);
                    });
                }
            }
        };

        // Drop existing latitude and longitude columns
        $dropColumnsIfExist('alarm_notification', ['latitude', 'longitude']);
        $dropColumnsIfExist('city', ['latitude', 'longitude']);
        $dropColumnsIfExist('position', ['latitude', 'longitude']);
        $dropColumnsIfExist('refuel', ['latitude', 'longitude']);

        // Add generated columns for alarm_notification
        $this->db()->unprepared('
            ALTER TABLE `alarm_notification`
            ADD COLUMN `latitude` DOUBLE AS (ROUND(ST_Y(`point`), 5)) STORED,
            ADD COLUMN `longitude` DOUBLE AS (ROUND(ST_X(`point`), 5)) STORED;
        ');

        // Add generated columns for city
        $this->db()->unprepared('
            ALTER TABLE `city`
            ADD COLUMN `latitude` DOUBLE AS (ROUND(ST_Y(`point`), 5)) STORED,
            ADD COLUMN `longitude` DOUBLE AS (ROUND(ST_X(`point`), 5)) STORED;
        ');

        // Add generated columns for position
        $this->db()->unprepared('
            ALTER TABLE `position`
            ADD COLUMN `latitude` DOUBLE AS (ROUND(ST_Y(`point`), 5)) STORED,
            ADD COLUMN `longitude` DOUBLE AS (ROUND(ST_X(`point`), 5)) STORED;
        ');

        // Add generated columns for refuel
        $this->db()->unprepared('
            ALTER TABLE `refuel`
            ADD COLUMN `latitude` DOUBLE AS (ROUND(ST_Y(`point`), 5)) STORED,
            ADD COLUMN `longitude` DOUBLE AS (ROUND(ST_X(`point`), 5)) STORED;
        ');
    }

    /**
     * @return void
     */
    public function down(): void
    {
        // Nothing to rollback - the migration fixes MariaDB compatibility
        // Rolling back would restore the broken ST_LATITUDE/ST_LONGITUDE functions
        // which don't work in MariaDB
    }
};
