<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourierStatsController extends Controller
{
    public function getStats()
    {
        try {
            // Test the connection first
            DB::connection('courier')->getPdo();
            
            $stats = DB::connection('courier')->select("
                SELECT 
                    'Сегодня' AS period,
                    COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) AS delivered_count,
                    COUNT(CASE WHEN time_put IS NULL THEN 1 END) AS return_count
                FROM address
                WHERE date_put = CURDATE()

                UNION ALL

                SELECT 
                    'Вчера' AS period,
                    COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) AS delivered_count,
                    COUNT(CASE WHEN time_put IS NULL THEN 1 END) AS return_count
                FROM address
                WHERE date_put = CURDATE() - INTERVAL 1 DAY

                UNION ALL

                SELECT 
                    'Текущий месяц' AS period,
                    COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) AS delivered_count,
                    COUNT(CASE WHEN time_put IS NULL THEN 1 END) AS return_count
                FROM address
                WHERE date_put BETWEEN DATE_FORMAT(CURDATE(), '%Y-%m-01') AND LAST_DAY(CURDATE())

                UNION ALL

                SELECT 
                    'Прошлый месяц' AS period,
                    COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) AS delivered_count,
                    COUNT(CASE WHEN time_put IS NULL THEN 1 END) AS return_count
                FROM address
                WHERE date_put BETWEEN DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') 
                    AND LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))

                ORDER BY 
                    CASE period
                        WHEN 'Сегодня' THEN 1
                        WHEN 'Вчера' THEN 2
                        WHEN 'Текущий месяц' THEN 3
                        WHEN 'Прошлый месяц' THEN 4
                        ELSE 5
                    END
            ");

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Courier Stats Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics',
                'debug_error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ] : null
            ], 500);
        }
    }
}
