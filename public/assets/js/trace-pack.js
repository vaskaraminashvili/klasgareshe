(function () {
    if (window.__kidzioTrace) {
        return;
    }

    window.__kidzioTrace = true;

    let drawing = false;
    let points = [];

    function svgOf(event) {
        const node = event.target && event.target.closest ? event.target.closest('#traceSvg') : null;

        return node || null;
    }

    function svgPoint(svg, event) {
        const rect = svg.getBoundingClientRect();
        const x = rect.width === 0 ? 0 : ((event.clientX - rect.left) / rect.width) * 200;
        const y = rect.height === 0 ? 0 : ((event.clientY - rect.top) / rect.height) * 220;

        return [x, y];
    }

    function paint(svg) {
        const path = svg.querySelector('#userPath');

        if (!path) {
            return;
        }

        if (!points.length) {
            path.setAttribute('d', '');

            return;
        }

        let d = 'M' + points[0][0].toFixed(1) + ' ' + points[0][1].toFixed(1);

        for (let i = 1; i < points.length; i++) {
            d += ' L' + points[i][0].toFixed(1) + ' ' + points[i][1].toFixed(1);
        }

        path.setAttribute('d', d);
    }

    function componentId(svg) {
        const host = svg.closest('[wire\\:id]');

        return host ? host.getAttribute('wire:id') : null;
    }

    document.addEventListener('pointerdown', function (event) {
        const svg = svgOf(event);

        if (!svg) {
            return;
        }

        drawing = true;
        points.push(svgPoint(svg, event));
        paint(svg);
    });

    document.addEventListener('pointermove', function (event) {
        if (!drawing) {
            return;
        }

        const svg = document.getElementById('traceSvg');

        if (!svg) {
            return;
        }

        points.push(svgPoint(svg, event));
        paint(svg);
    });

    document.addEventListener('pointerup', function () {
        if (!drawing) {
            return;
        }

        drawing = false;
        const svg = document.getElementById('traceSvg');
        const id = svg ? componentId(svg) : null;
        const snapshot = points.slice();

        if (!id || !window.Livewire || snapshot.length === 0) {
            return;
        }

        const component = window.Livewire.find(id);

        if (!component) {
            return;
        }

        Promise.resolve(component.submitTrace(snapshot)).then(function (accuracy) {
            if (typeof accuracy === 'number' && accuracy < 60) {
                points = [];

                if (svg) {
                    paint(svg);
                }
            }
        });
    });

    document.addEventListener('click', function (event) {
        const button = event.target && event.target.closest ? event.target.closest('#restartBtn') : null;

        if (!button) {
            return;
        }

        points = [];
        const svg = document.getElementById('traceSvg');

        if (svg) {
            paint(svg);
        }
    });
})();
