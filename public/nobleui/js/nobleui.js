(function () {
    function onReady(cb) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', cb);
        } else {
            cb();
        }
    }

    function initSidebarToggle() {
        var toggle = document.querySelector('[data-sidebar-toggle]');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
    }

    function initDropdowns() {
        document.querySelectorAll('[data-dropdown-toggle]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                var container = button.closest('.dropdown');
                if (!container) {
                    return;
                }
                container.classList.toggle('open');
            });
        });

        document.addEventListener('click', function (event) {
            document.querySelectorAll('.dropdown.open').forEach(function (dropdown) {
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.remove('open');
                }
            });
        });
    }

    function initMiniChart(canvasSelector) {
        var canvas = document.querySelector(canvasSelector);
        if (!canvas || canvas.tagName !== 'CANVAS') {
            return;
        }

        var ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }

        var points = [42, 58, 47, 72, 66, 89, 77];
        var width = canvas.width;
        var height = canvas.height;
        var padding = 20;
        var maxVal = Math.max.apply(Math, points);

        ctx.clearRect(0, 0, width, height);
        ctx.lineWidth = 2;
        ctx.strokeStyle = '#6571ff';
        ctx.fillStyle = 'rgba(101,113,255,0.12)';

        ctx.beginPath();
        points.forEach(function (value, index) {
            var x = padding + (index * (width - (padding * 2)) / (points.length - 1));
            var y = height - padding - ((value / maxVal) * (height - (padding * 2)));
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.stroke();

        ctx.lineTo(width - padding, height - padding);
        ctx.lineTo(padding, height - padding);
        ctx.closePath();
        ctx.fill();
    }

    window.NobleUI = {
        initMiniChart: initMiniChart,
        initDatePicker: function () {
            return true;
        }
    };

    onReady(function () {
        initSidebarToggle();
        initDropdowns();
    });
})();
