<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Trading Positions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link href="styles.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
  <div class="container-fluid table-container px-5">
    <h3 class="text-center mb-4 title">📊 Trading Positions</h3>
    <div class="table-responsive">
      <table class="table table-bordered table-dark table-hover">
        <thead>
          <tr>
            <th>ID</th>
            <th>Symbol</th>
            <th>Direction</th>
            <th>Entry Price</th>
            <th>Volume</th>
            <th>Mark Price</th>
            <th>Break-even Price</th>
            <th>Liquidation Price</th>
            <th>Change %</th>
            <th>Unrealized Profit</th>
            <th>Trailing Stop</th>
            <th>Take Profit</th>
            <th>Amount</th>
            <th>Last Trade Time</th>
            <th>Status</th>
            <th>Info</th>
          </tr>
        </thead>
        <tbody id="positionsTableBody">
          <tr><td colspan="14" class="text-center text-secondary">Cargando datos...</td></tr>
        </tbody>
      </table>
    </div> <br>
    <div class="row">
      <div class="col-md-6">
        <div class="table-responsive">
          <table class="table table-bordered table-dark table-striped table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Symbol</th>
                <th>DateTime</th>
                <th>Info</th>
              </tr>
            </thead>
            <tbody id="info">
              <tr><td colspan="14" class="text-center text-secondary">Cargando datos...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="table-responsive">
          <h3>5 min chart signals</h3>
          <table class="table table-bordered table-dark">
            <thead>
              <tr>
                <th></th>
                <th></th>
                <th colspan='3' class='h4Chart'>4h Chart</th>
                <th colspan='3' class='h1Chart'>1h Chart</th>
                <th colspan='7' class='min5Chart'>5min Chart</th>
              </tr>
              <tr>
                <th>ID</th>
                <th>Symbol</th>
                <th>RSI 4h</th>
                <th>Open Time 4h</th>
                <th>Open 4h</th>
                <th>RSI 1h</th>
                <th>Open Time 1h</th>
                <th>Open 1h</th>
                <th>RSI</th>
                <th>Open</th>
                <th>Close</th>
                <th>MACD GAP</th>
                <th>VI+</th>
                <th>VI-</th>
                <th>VI GAP %</th>
              </tr>
            </thead>
            <tbody id="signals">
              <tr><td colspan="14" class="text-center text-secondary">Cargando datos...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    function loadPositions() {
      $(".fader").fadeOut();
      $.ajax({
        url: "get_positions.php",
        method: "GET",
        dataType: 'json',
        success: function(response) {
          $("#positionsTableBody").html(response.positions_table);
          $("#info").html(response.logs_table);
          $("#signals").html(response.signals_table);
          $(".fader").fadeOut(200).fadeIn(300);
        },
        error: function() {
          $("#positionsTableBody").html("<tr><td colspan='14' class='text-danger text-center'>Error al cargar los datos.</td></tr>");
        }
      });
    }

    // Cargar al iniciar
    $(document).ready(function() {
      loadPositions();
      setInterval(loadPositions, 3000); // Actualiza cada 5 segundos
    });

  </script>
</body>
</html>
