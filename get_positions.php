<?php
require_once "db_config.php";

// Mostrar errores en pantalla
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


/* ============================================================
   FUNCIONES AUXILIARES
============================================================ */

// --- Formatear hora a UTC-6 ---
function convert_time($datetime_str) {
    if (!$datetime_str || $datetime_str === "0000-00-00 00:00:00") {
        return "";
    }
    $dt = new DateTime($datetime_str, new DateTimeZone('UTC'));
    $dt->modify('-6 hours');
    return $dt->format('H:i');
}

// --- Clasificar RSI ---
function rsi_light($rsi_value) {
    if ($rsi_value < 25) {
        return "rsi_way_over_sold";
    } elseif ($rsi_value < 30 && $rsi_value >= 25) {
        return "rsi_over_sold";
    } elseif ($rsi_value >= 30 && $rsi_value <= 35) {
        return "rsi_low";
    } elseif ($rsi_value >= 65 && $rsi_value <= 70) {
        return "rsi_high";
    } elseif ($rsi_value > 70) {
        return "rsi_over_bought";
    } else {
        return "";
    }
}


/* ============================================================
   FUNCIÓN PRINCIPAL
============================================================ */
function get_positions_and_logs() {

    global $conn;
    $jsondata = [];
    $active_symbols = []; // asegurado siempre

    /* ------------------------------------------------------------
        POSITIONS
    ------------------------------------------------------------ */
    $query = "SELECT * FROM trading_positions 
              WHERE position_status = 1 
              ORDER BY last_trade_time ASC";

    $result = $conn->query($query);

    if (!$result) {
        die("SQL Error (positions): " . $conn->error);
    }

    $row_count = $result->num_rows;
    $positions_table = "";
    $n = 0;
    $sum_pnl = 0;
    $sum_vol = 0;

    if ($row_count > 0) {

        while ($row = $result->fetch_assoc()) {

            $n++;
            $symbol = $row['symbol'];
            $active_symbols[] = $symbol;

            $unrealized = (float)$row['unrealized_profit'];
            $break_even = $row['breakeven_price'];
            $trailing_stop = $row['trailing_stop'];
            $sum_pnl += $unrealized;

            $entry = (float)$row['entry_price'];
            $mark = (float)$row['mark_price'];
            $volume = round($entry * $row['position_amount'], 4);

            $sum_vol += abs($volume);
            $change = round((($mark - $entry) / $entry) * 100, 4);

            $pnl_bg = $unrealized > 0 ? "profit" : "loss";
            $side = $row['position_direction'] === "LONG" ? "long" : "short";
            $trail = $side == 'long' && $trailing_stop > $break_even ? "profit" : ($side == 'short' && $trailing_stop < $break_even ? "loss" : "");
            // $trail = $row['trailing_stop'] > 0 ? ($row['trailing_stop'] > )"trailing_on" : "";


            $positions_table .= "
                <tr>
                    <td>{$n}</td>
                    <td>{$symbol}</td>
                    <td class='{$side}'>{$row['position_direction']}</td>
                    <td>{$entry}</td>
                    <td>{$volume}</td>
                    <td>{$mark}</td>
                    <td>{$break_even}</td>
                    <td>{$row['liquidation_price']}</td>
                    <td>{$change}</td>
                    <td class='{$pnl_bg}'>{$unrealized}</td>
                    <td class='{$trail}'>{$trailing_stop}</td>
                    <td>{$row['take_profit']}</td>
                    <td>{$row['position_amount']}</td>
                    <td>{$row['last_trade_time']}</td>
                    <td>{$row['position_status']}</td>
                    <td>{$row['info']}</td>
                </tr>";
        }

        $pnl_over_vol = $sum_vol > 0 ? round(($sum_pnl / $sum_vol) * 100, 2) : 0;
        $pnl_color = ($sum_pnl > 0) ? "profit_color" : "loss_color";
        $positions_table .= "
            <tr>
                <td colspan='4'></td>
                <td>{$sum_vol}</td>
                <td colspan='3'></td>
                <td>{$pnl_over_vol}</td>
                <td><span class='fader {$pnl_color}'>{$sum_pnl}</span></td>
                <td colspan='6'></td>
            </tr>";

        $jsondata['positions_table'] = $positions_table;
        $jsondata['positions_count'] = $row_count;
    } else {
        $jsondata['positions_table'] = "Sin posiciones activas.";
    }

    $active_symbols = array_unique($active_symbols); // seguridad

    /* ------------------------------------------------------------
        LOGS
    ------------------------------------------------------------ */
    $log_query = "SELECT ps.id, ps.symbol, pi.info, ps.updated_at
                  FROM position_state ps
                  JOIN positions_info pi ON ps.state = pi.state
                  WHERE ps.status_ = 1
                  ORDER BY ps.updated_at DESC";

    $logs = $conn->query($log_query);

    if (!$logs) {
        die("SQL Error (logs): " . $conn->error);
    }

    $logs_table = "";
    $n = 0;

    if ($logs->num_rows > 0) {

        while ($row = $logs->fetch_assoc()) {
            $n++;
            $logs_table .= "
                <tr>
                    <td>{$n}</td>
                    <td>{$row['symbol']}</td>
                    <td>{$row['updated_at']}</td>
                    <td>{$row['info']}</td>
                </tr>";
        }

        $jsondata['logs_table'] = $logs_table;

    } else {
        $jsondata['logs_table'] = "Sin logs para mostrar.";
    }

    /* ------------------------------------------------------------
        TRADE SIGNALS
    ------------------------------------------------------------ */
    $signals_query = "SELECT 
                        ts.symbol,
                        ts4.RSI AS rsi4h,
                        ts4.open_time AS open_time4h,
                        ts4.open AS open4h,
                        ts1.RSI AS rsi1h,
                        ts1.open_time AS open_time1h,
                        ts1.open AS open1h,
                        ts15.RSI AS rsi15m,
                        ts15.open_time AS open_time15m,
                        ts15.open AS open15m,
                        ts.RSI,
                        ts.open,
                        ts.close,
                        ts.MACD_gap_pct,
                        ts.VI_plus,
                        ts.VI_minus,
                        ts.VI_gap_pct
                    FROM trade_signals ts
                    JOIN trade_signals_15m ts15 ON ts.symbol = ts15.symbol
                    JOIN trade_signals_1h ts1 ON ts.symbol = ts1.symbol
                    JOIN trade_signals_4h ts4 ON ts.symbol = ts4.symbol
                    ORDER BY ts.symbol ASC";

    $trade_signals = $conn->query($signals_query);

    if (!$trade_signals) {
        die("SQL Error (signals): " . $conn->error);
    }

    $signals_table = "";
    $n = 0;

    if ($trade_signals->num_rows > 0) {

        while ($row = $trade_signals->fetch_assoc()) {

            $n++;
            $symbol = $row['symbol'];
            $active_class = in_array($symbol, $active_symbols) ? $side : "";

            // RSI classes
            $rsi_class_4h = rsi_light($row['rsi4h']);
            $rsi_class_1h = rsi_light($row['rsi1h']);
            $rsi_class_15m = rsi_light($row['rsi15m']);
            $rsi_class_5m = rsi_light($row['RSI']);

            // Candle
            $close_indicator = ($row['close'] < $row['open']) ? "close_down" : "close_up";

            // Vortex
            $viplus_class = ($row['VI_plus'] > 1.20) ? "vi_plus_top" :
                            (($row['VI_plus'] >= 1.10) ? "vi_plus_mid" : "");

            $viminus_class = ($row['VI_minus'] > 1.20) ? "vi_minus_top" :
                             (($row['VI_minus'] >= 1.10) ? "vi_minus_mid" : "");

            $vigap_class = (abs($row['VI_gap_pct']) > 40 && abs($row['VI_gap_pct'] <= 50) ) ? "vi_gap_max" :
                           (abs($row['VI_gap_pct'] > 50) ? "vi_gap_max_2" : (abs($row['VI_gap_pct']) < 10 ? "vi_gap_min" : ""));

            $signals_table .= "
                <tr>
                    <td>{$n}</td>
                    <td class='{$active_class}'>{$symbol}</td>
                    <td class='{$rsi_class_4h}'>{$row['rsi4h']}</td>
                    <td class='{$active_class}'>{$row['open4h']}</td>
                    <td class='{$rsi_class_1h}'>{$row['rsi1h']}</td>
                    <td class='{$active_class}'>{$row['open1h']}</td>
                    <td class='{$rsi_class_15m}'>{$row['rsi15m']}</td>
                    <td class='{$active_class}'>{$row['open15m']}</td>
                    <td class='{$rsi_class_5m}'>{$row['RSI']}</td>
                    <td class='{$active_class}'>{$row['open']}</td>
                    <td class='{$close_indicator}'>{$row['close']}</td>
                    <td>{$row['MACD_gap_pct']}</td>
                    <td class='{$viplus_class}'>{$row['VI_plus']}</td>
                    <td class='{$viminus_class}'>{$row['VI_minus']}</td>
                    <td class='{$vigap_class}'>{$row['VI_gap_pct']}</td>
                </tr>";

                $jsondata['open_time_4h'] = convert_time($row['open_time4h']);
                $jsondata['open_time_1h'] = convert_time($row['open_time1h']);
                $jsondata['open_time_15m'] = convert_time($row['open_time15m']);
        }

        $jsondata['signals_table'] = $signals_table;
        // $jsondata['open_time_15m'] = $signals_table;

    } else {
        $jsondata['signals_table'] = "Sin signals para mostrar.";
    }


    /* ------------------------------------------------------------
        OUTPUT JSON
    ------------------------------------------------------------ */
    $jsondata['status'] = "ok";
    echo json_encode($jsondata, JSON_UNESCAPED_SLASHES);
}



/* ============================================================
   EJECUCIÓN
============================================================ */

get_positions_and_logs();

?>
