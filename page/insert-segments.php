<?php

namespace WinnerApp;

require_once(__DIR__ . '/../lib/config.php');

Utils::htmlHeader($title = "Segments");

$allCoinsToProcess = CoinService::fetchSymbolsToProcess();
?>
<script>
    const _pageApp = {
        toDoCoins: <?php echo json_encode($allCoinsToProcess) ?>,
        doneCoins: [],
        errorCoins: [],
        doingCoins: [],
        urlEndPoint: '/actions/insert-segments',
        maximumParallelTasks: 8,
        onLineRequests: 0,
        tickerInterval: null,
        init: function() {
            this.initTicker();
        },
        initTicker: function() {
            const _this = this;
            _this.tickerInterval = setInterval(function() {
                _this.refreshUi();
            }, 700);
        },
        runCycle: function() {
            const _this = this;
            /// Finished
            if (_this.toDoCoins.length === 0) {
                return;
            };

            while (_this.doingCoins.length < _this.maximumParallelTasks) {
                const _symbolToProcess = _this.toDoCoins[0];
                _this.toDoCoins = _this.removeFromArray(_this.toDoCoins, _symbolToProcess);
                _this.doingCoins.push(_symbolToProcess);

                /// between 300, 1200
                const randomTimeout = Math.floor(Math.random() * (2000 - 300 + 1)) + 1200;
                setTimeout(function() {
                    _this.doRequest(_symbolToProcess);
                }, randomTimeout);
            }
        },
        removeFromArray: function(arr, val) {
            const _valToRemove = val;
            arr = jQuery.grep(arr, function(value) {
                return value != _valToRemove;
            });

            return arr;
        },
        isLoading: function() {
            return this.onLineRequests > 0;
        },
        refreshUi: function() {
            this.runCycle();
            console.log('toDoCoins ->');
            console.log(this.toDoCoins);

            console.log('doingCoins ->');
            console.log(this.doingCoins);

            console.log('doneCoins ->');
            console.log(this.doneCoins);

            console.log('errorCoins ->');
            console.log(this.errorCoins);
        },
        doRequest: function(coinSymbol) {
            const _this = this;
            const _coinSymbol = coinSymbol;
            $.ajax({
                url: _this.urlEndPoint,
                async: true,
                type: 'POST',
                dataType: 'json',
                timeout: 1000 * 60 * 1,
                data: {
                    "<?php echo SegmentInserter::PARAM_COIN_SYMBOL ?>": _coinSymbol
                },
                beforeSend: function() {
                    _this.onLineRequests++;                    
                },
                complete: function(data) {
                    _this.doingCoins = _this.removeFromArray(_this.doingCoins, _coinSymbol);
                    _this.doneCoins.push(_coinSymbol);

                    _this.onLineRequests--;
                },
                success: function(data) {

                },
                error: function(jqXHR, error, errorThrown) {
                    console.log(error + ':' + _coinSymbol);
                    _this.errorCoins.push(_coinSymbol);
                },
            });
        }
    };

    $(document).ready(function() {
        _pageApp.init();
    });
</script>
<div class="page-price-fetcher">

    Do a request to

</div>

<?php Utils::htmlFooter(); ?>