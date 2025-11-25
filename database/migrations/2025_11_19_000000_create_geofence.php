<?php declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Domains\CoreApp\Migration\MigrationAbstract;

return new class() extends MigrationAbstract {
    /**
     * @return void
     */
    public function up(): void
    {
        $this->tables();
        $this->keys();
    }

    /**
     * @return void
     */
    protected function tables(): void
    {
        Schema::create('geofence', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('type')->default('polygon'); // polygon, circle
            $table->text('description')->nullable();

            $table->geometry('geom', 'polygon', 4326)->nullable()->comment('Polygon or Circle geometry');
            
            // Circle specific fields
            $table->geometry('center', 'point', 4326)->nullable()->comment('Center point for circle type');
            $table->decimal('radius', 10, 2)->nullable()->comment('Radius in meters for circle type');

            $table->string('color')->default('#FF0000')->comment('Color for map display');
            $table->boolean('enabled')->default(1);

            $this->timestamps($table);

            $table->unsignedBigInteger('user_id');
        });

        // Pivot table for device-geofence relationship
        Schema::create('device_geofence', function (Blueprint $table) {
            $table->id();

            $table->enum('mode', ['inside', 'outside'])->default('inside')->comment('Alarm when inside or outside');
            $table->boolean('enabled')->default(1);

            $this->timestamps($table);

            $table->unsignedBigInteger('device_id');
            $table->unsignedBigInteger('geofence_id');

            $table->unique(['device_id', 'geofence_id']);
        });

        // Add geofence_id to alarm_notification
        Schema::table('alarm_notification', function (Blueprint $table) {
            $table->unsignedBigInteger('geofence_id')->nullable()->after('alarm_id');
        });
    }

    /**
     * @return void
     */
    protected function keys(): void
    {
        Schema::table('geofence', function (Blueprint $table) {
            $this->foreignOnDeleteCascade($table, 'user');
            
            $table->index('enabled');
            $table->index(['user_id', 'enabled']);
        });

        Schema::table('device_geofence', function (Blueprint $table) {
            $this->foreignOnDeleteCascade($table, 'device');
            $this->foreignOnDeleteCascade($table, 'geofence');
            
            $table->index('enabled');
        });

        Schema::table('alarm_notification', function (Blueprint $table) {
            $table->foreign('geofence_id')
                ->references('id')
                ->on('geofence')
                ->onDelete('set null');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('alarm_notification', function (Blueprint $table) {
            $table->dropForeign(['geofence_id']);
            $table->dropColumn('geofence_id');
        });

        Schema::dropIfExists('device_geofence');
        Schema::dropIfExists('geofence');
    }
};
