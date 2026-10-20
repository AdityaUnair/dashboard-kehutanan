/* Grafik dashboard. Data berasal dari window.DATA yang diisi controller (lihat dashboard/index.blade.php). */
(() => {
    const D = window.DATA;
    const W = {
        hijau: '#1d6949', hijauMuda: '#7fb894', mint: '#cfe6d7', oranye: '#bc6b2c', api: '#c4452b',
        kayu: '#8a5a2b', teal: '#2a7f8f', emas: '#c9a227', abu: '#9aa9a0', tinta: '#173629',
    };
    const WARNA_WILAYAH = {
        'Sumatera': '#c4452b', 'Kalimantan': '#1d6949', 'Jawa': '#c9a227', 'Bali & Nusa Tenggara': '#2a7f8f',
        'Sulawesi': '#8a5a2b', 'Maluku': '#7a5ea8', 'Papua': '#5f7468',
    };

    const angka = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
    const ringkas = (v) => {
        const a = Math.abs(v);
        if (a >= 1e6) return angka.format(v / 1e6) + ' jt';
        if (a >= 1e3) return angka.format(v / 1e3) + ' rb';
        return angka.format(v);
    };

    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.font.size = 11.5;
    Chart.defaults.color = '#5f7468';
    Chart.defaults.borderColor = '#e6eee8';
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
    Chart.defaults.plugins.tooltip.callbacks.label = (c) =>
        `${c.dataset.label ? c.dataset.label + ': ' : ''}${angka.format(c.parsed.x !== undefined && c.chart.options.indexAxis === 'y' ? c.parsed.x : c.parsed.y)}`;
    Chart.defaults.maintainAspectRatio = false;

    const sumbuRingkas = { ticks: { callback: (v) => ringkas(v) } };
    const buat = (id, config) => new Chart(document.getElementById(id), config);

    // ---------------------------------------------------------------- 1. Kawasan (D1)
    const kw = D.kawasan;
    buat('chartKawasanProvinsi', {
        type: 'bar',
        data: {
            labels: kw.provinsi.map((p) => p.nama),
            datasets: [
                { label: 'Lindung', data: kw.provinsi.map((p) => p.lindung), backgroundColor: W.hijau },
                { label: 'Konservasi', data: kw.provinsi.map((p) => p.konservasi), backgroundColor: W.teal },
                { label: 'Produksi', data: kw.provinsi.map((p) => p.produksi), backgroundColor: W.emas },
            ],
        },
        options: {
            indexAxis: 'y',
            scales: { x: { stacked: true, ...sumbuRingkas }, y: { stacked: true, ticks: { autoSkip: false, font: { size: 10.5 } } } },
            plugins: { legend: { position: 'top' } },
        },
    });
    buat('chartKawasanFungsi', {
        type: 'doughnut',
        data: {
            labels: Object.keys(kw.fungsi),
            datasets: [{ data: Object.values(kw.fungsi), backgroundColor: [W.hijau, W.teal, W.emas, W.oranye, W.kayu], borderWidth: 2 }],
        },
        options: {
            cutout: '60%',
            plugins: {
                legend: { position: 'right' },
                tooltip: { callbacks: { label: (c) => `${c.label}: ${ringkas(c.parsed)} ha` } },
            },
        },
    });
    buat('chartKawasanTren', {
        type: 'line',
        data: {
            labels: Object.keys(kw.nasional),
            datasets: [{ data: Object.values(kw.nasional).map(Number), borderColor: W.hijau, backgroundColor: W.hijau, tension: .3 }],
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { min: 125e6, max: 126.2e6, ...sumbuRingkas } } },
    });

    // ---------------------------------------------------------------- 2. Tutupan (D2)
    const tt = D.tutupan;
    buat('chartTutupanTren', {
        data: {
            labels: tt.nasional.map((r) => r.tahun),
            datasets: [
                { type: 'line', label: '% daratan berhutan', data: tt.nasional.map((r) => r.persen), borderColor: W.oranye,
                    backgroundColor: W.oranye, yAxisID: 'y2', tension: .3 },
                { type: 'bar', label: 'Tutupan hutan (ha)', data: tt.nasional.map((r) => r.hutan), backgroundColor: W.teal, yAxisID: 'y' },
            ],
        },
        options: {
            scales: {
                y: { min: 90e6, ...sumbuRingkas, title: { display: true, text: 'ha' } },
                y2: { position: 'right', min: 48, max: 53, grid: { display: false }, ticks: { callback: (v) => v + '%' } },
            },
        },
    });
    buat('chartTutupanProvinsi', {
        type: 'bar',
        data: {
            labels: tt.turun.map((p) => p.nama),
            datasets: [{ label: 'Perubahan (ha)', data: tt.turun.map((p) => p.selisih),
                backgroundColor: tt.turun.map((p) => (p.selisih < 0 ? W.api : W.hijau)) }],
        },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: sumbuRingkas } },
    });

    // ---------------------------------------------------------------- 3. Deforestasi (D5)
    const df = D.deforestasi;
    buat('chartDeforTren', {
        type: 'bar',
        data: {
            labels: df.nasional.map((r) => r.periode),
            datasets: [
                { label: 'Di dalam kawasan hutan', data: df.nasional.map((r) => r.dalam), backgroundColor: W.oranye },
                { label: 'Di luar kawasan (APL)', data: df.nasional.map((r) => r.luar), backgroundColor: W.emas },
            ],
        },
        options: { scales: { x: { stacked: true }, y: { stacked: true, ...sumbuRingkas, title: { display: true, text: 'ha/tahun' } } } },
    });
    buat('chartDeforProvinsi', {
        type: 'bar',
        data: {
            labels: df.top.map((p) => p.nama),
            datasets: [{ label: 'Deforestasi netto (ha)', data: df.top.map((p) => p.total), backgroundColor: W.oranye }],
        },
        options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: sumbuRingkas } },
    });

    // ---------------------------------------------------------------- 4 & 5. Karhutla (D3) dan Kayu (D4): tren + top 10 per tahun
    const trenDanTop = (data, idTren, idTop, idPilih, warna, satuan) => {
        buat(idTren, {
            type: 'bar',
            data: {
                labels: Object.keys(data.nasional),
                datasets: [{ label: satuan, data: Object.values(data.nasional), backgroundColor: warna, borderRadius: 4 }],
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { ...sumbuRingkas, title: { display: true, text: satuan } } } },
        });

        const top = buat(idTop, {
            type: 'bar',
            data: { labels: [], datasets: [{ label: satuan, data: [], backgroundColor: warna }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: sumbuRingkas } },
        });
        const pilih = document.getElementById(idPilih);
        const perbarui = () => {
            const i = data.tahun.indexOf(Number(pilih.value));
            const urut = data.provinsi.map((p) => ({ nama: p.nama, v: p.nilai[i] })).sort((a, b) => b.v - a.v).slice(0, 10);
            top.data.labels = urut.map((p) => p.nama);
            top.data.datasets[0].data = urut.map((p) => p.v);
            top.update();
        };
        pilih.addEventListener('change', perbarui);
        perbarui();
    };
    trenDanTop(D.karhutla, 'chartKarhutlaTren', 'chartKarhutlaProvinsi', 'pilihTahunKarhutla', W.api, 'ha');
    trenDanTop(D.kayu, 'chartKayuTren', 'chartKayuProvinsi', 'pilihTahunKayu', W.kayu, 'm³');

    // ---------------------------------------------------------------- G1. Indeks gabungan
    const ix = D.indeks;
    const warnaIndeks = [W.teal, W.api, W.kayu, W.oranye];
    buat('chartIndeks', {
        type: 'line',
        data: {
            labels: ix.tahun,
            datasets: Object.entries(ix.deret).map(([label, data], i) => ({
                label, data, borderColor: warnaIndeks[i], backgroundColor: warnaIndeks[i], tension: .25,
                borderWidth: 2.5, pointRadius: 3.5, spanGaps: false,
            })),
        },
        options: {
            plugins: { tooltip: { callbacks: { label: (c) => `${c.dataset.label}: ${angka.format(c.parsed.y)}` } } },
            scales: { y: { type: 'logarithmic', title: { display: true, text: 'indeks (skala log)' },
                ticks: { callback: (v) => ([25, 50, 100, 200, 300].includes(v) ? v : '') } } },
        },
        plugins: [{
            id: 'garis100',
            afterDraw(chart) {
                const y = chart.scales.y.getPixelForValue(100);
                const { ctx, chartArea } = chart;
                ctx.save();
                ctx.strokeStyle = '#9aa9a0';
                ctx.setLineDash([4, 4]);
                ctx.beginPath();
                ctx.moveTo(chartArea.left, y);
                ctx.lineTo(chartArea.right, y);
                ctx.stroke();
                ctx.restore();
            },
        }],
    });

    // ---------------------------------------------------------------- G2. Kawasan vs tutupan
    const kt = D.kawasanVsTutupan;
    buat('chartKawasanVsTutupan', {
        type: 'bar',
        data: {
            labels: kt.provinsi.map((p) => p.nama),
            datasets: [
                { label: 'Kawasan hutan (D1)', data: kt.provinsi.map((p) => p.kawasan), backgroundColor: W.mint, borderColor: W.hijau, borderWidth: 1 },
                { label: 'Tutupan hutan (D2)', data: kt.provinsi.map((p) => p.hutan), backgroundColor: W.teal },
            ],
        },
        options: { indexAxis: 'y', scales: { x: sumbuRingkas, y: { ticks: { autoSkip: false } } } },
    });

    // ---------------------------------------------------------------- G3. Benang merah (bubble)
    const bm = D.benangMerah;
    const maksKayu = Math.max(...bm.titik.map((t) => t.kayu));
    buat('chartBenangMerah', {
        type: 'bubble',
        data: {
            datasets: bm.wilayah.map((w) => ({
                label: w,
                backgroundColor: WARNA_WILAYAH[w] + 'b3',
                borderColor: WARNA_WILAYAH[w],
                data: bm.titik.filter((t) => t.wilayah === w).map((t) => ({
                    x: t.karhutla, y: t.deforestasi, r: 4 + 22 * Math.sqrt(t.kayu / maksKayu), nama: t.nama, kayu: t.kayu,
                })),
            })),
        },
        options: {
            scales: {
                x: { ...sumbuRingkas, grace: '8%', title: { display: true, text: 'Total karhutla 2018–2022 (ha)' } },
                y: { ...sumbuRingkas, grace: '15%', title: { display: true, text: 'Total deforestasi netto 2018–2022 (ha)' } },
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: (c) => {
                            const p = c.raw;
                            return [`${p.nama}`, `Karhutla: ${ringkas(p.x)} ha`, `Deforestasi: ${ringkas(p.y)} ha`, `Kayu: ${ringkas(p.kayu)} m³`];
                        },
                    },
                },
            },
        },
        plugins: [{
            id: 'labelTitik',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                ctx.save();
                ctx.font = '600 10.5px Inter, sans-serif';
                ctx.fillStyle = '#173629';
                chart.data.datasets.forEach((ds, i) => {
                    chart.getDatasetMeta(i).data.forEach((el, j) => {
                        const p = ds.data[j];
                        if (p.x > 150000 || p.y > 100000 || p.kayu > maksKayu * 0.3) ctx.fillText(p.nama, el.x + 8, el.y - 8);
                    });
                });
                ctx.restore();
            },
        }],
    });
})();
