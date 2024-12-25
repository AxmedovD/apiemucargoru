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
                    'Today' AS period,
                    COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) AS delivered_count,
                    COUNT(CASE WHEN time_put IS NULL THEN 1 END) AS return_count,
                    ROUND(SUM(price), 2) AS total_price,
                    CASE 
                        WHEN COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) = 0 
                             THEN 0
                        ELSE 
                             ROUND(
                                SUM(CASE WHEN time_put IS NOT NULL THEN price ELSE 0 END) 
                                / COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END),
                                2
                             )
                    END AS avg_price_delivered,
                    CASE 
                        WHEN COUNT(CASE WHEN time_put IS NULL THEN 1 END) = 0 
                             THEN 0
                        ELSE 
                             ROUND(
                                SUM(CASE WHEN time_put IS NULL THEN price ELSE 0 END) 
                                / COUNT(CASE WHEN time_put IS NULL THEN 1 END),
                                2
                             )
                    END AS avg_price_returned
                FROM address
                WHERE date_put = CURDATE()

                UNION ALL

                SELECT 
                    'Yesterday' AS period,
                    COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) AS delivered_count,
                    COUNT(CASE WHEN time_put IS NULL THEN 1 END) AS return_count,
                    ROUND(SUM(price), 2) AS total_price,
                    CASE 
                        WHEN COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) = 0 
                             THEN 0
                        ELSE 
                             ROUND(
                                SUM(CASE WHEN time_put IS NOT NULL THEN price ELSE 0 END) 
                                / COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END),
                                2
                             )
                    END AS avg_price_delivered,
                    CASE 
                        WHEN COUNT(CASE WHEN time_put IS NULL THEN 1 END) = 0 
                             THEN 0
                        ELSE 
                             ROUND(
                                SUM(CASE WHEN time_put IS NULL THEN price ELSE 0 END) 
                                / COUNT(CASE WHEN time_put IS NULL THEN 1 END),
                                2
                             )
                    END AS avg_price_returned
                FROM address
                WHERE date_put = CURDATE() - INTERVAL 1 DAY

                UNION ALL

                SELECT 
                    'Current Month' AS period,
                    COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) AS delivered_count,
                    COUNT(CASE WHEN time_put IS NULL THEN 1 END) AS return_count,
                    ROUND(SUM(price), 2) AS total_price,
                    CASE 
                        WHEN COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) = 0 
                             THEN 0
                        ELSE 
                             ROUND(
                                SUM(CASE WHEN time_put IS NOT NULL THEN price ELSE 0 END) 
                                / COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END),
                                2
                             )
                    END AS avg_price_delivered,
                    CASE 
                        WHEN COUNT(CASE WHEN time_put IS NULL THEN 1 END) = 0 
                             THEN 0
                        ELSE 
                             ROUND(
                                SUM(CASE WHEN time_put IS NULL THEN price ELSE 0 END) 
                                / COUNT(CASE WHEN time_put IS NULL THEN 1 END),
                                2
                             )
                    END AS avg_price_returned
                FROM address
                WHERE date_put BETWEEN 
                      DATE_FORMAT(CURDATE(), '%Y-%m-01') 
                      AND LAST_DAY(CURDATE())

                UNION ALL

                SELECT 
                    'Previous Month' AS period,
                    COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) AS delivered_count,
                    COUNT(CASE WHEN time_put IS NULL THEN 1 END) AS return_count,
                    ROUND(SUM(price), 2) AS total_price,
                    CASE 
                        WHEN COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END) = 0 
                             THEN 0
                        ELSE 
                             ROUND(
                                SUM(CASE WHEN time_put IS NOT NULL THEN price ELSE 0 END) 
                                / COUNT(CASE WHEN time_put IS NOT NULL THEN 1 END),
                                2
                             )
                    END AS avg_price_delivered,
                    CASE 
                        WHEN COUNT(CASE WHEN time_put IS NULL THEN 1 END) = 0 
                             THEN 0
                        ELSE 
                             ROUND(
                                SUM(CASE WHEN time_put IS NULL THEN price ELSE 0 END) 
                                / COUNT(CASE WHEN time_put IS NULL THEN 1 END),
                                2
                             )
                    END AS avg_price_returned
                FROM address
                WHERE date_put BETWEEN 
                      DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                      AND LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))

                ORDER BY 
                    CASE period
                        WHEN 'Today' THEN 1
                        WHEN 'Yesterday' THEN 2
                        WHEN 'Current Month' THEN 3
                        WHEN 'Previous Month' THEN 4
                        ELSE 5
                    END
            ");

            // Transform the stats to ensure numeric values
            $transformedStats = array_map(function($stat) {
                return [
                    'period' => $stat->period,
                    'delivered_count' => (int)$stat->delivered_count,
                    'return_count' => (int)$stat->return_count,
                    'total_price' => (float)$stat->total_price,
                    'avg_price_delivered' => (float)$stat->avg_price_delivered,
                    'avg_price_returned' => (float)$stat->avg_price_returned
                ];
            }, $stats);

            return response()->json([
                'success' => true,
                'data' => $transformedStats
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
