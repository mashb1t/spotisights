<?php

use App\Factory;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $callback = function (array $point) {
            // use UTC+0 for hour
            $playedAtDateTime = new Carbon($point['time']);

            return $playedAtDateTime->hour;
        };

        (new Factory)->getMigrateInfluxDataService()->addTagToTrackHistory('hour_of_day', $callback);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        throw new Exception('This migration is not reversible');
    }
};
