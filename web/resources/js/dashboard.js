/*
 * Grafik dashboard (Chart.js). Data berasal dari window.DATA yang diisi DashboardController.
 *
 * Aturan visual yang dipakai:
 *  - satu sumbu Y per grafik (tidak ada dua sumbu); satuan berbeda dibandingkan lewat indeks 2018 = 100
 *  - warna = identitas data, konsisten di seluruh halaman: tutupan biru, karhutla oranye, kayu hijau-toska,
 *    deforestasi kuning (palet lolos uji buta warna); abu-abu untuk konteks
 *  - batang tipis berujung bulat, celah 2px antarsegmen, garis kisi tipis, label langsung hanya di titik penting
 *  - setiap grafik punya tabel data pendamping ("Lihat tabel data")
 */
import Chart from 'chart.js/auto';

const D = window.DATA;

const W = {
    tutupan: '#2a78d6',
    karhutla: '#eb6834',
    kayu: '#1baf7a',
    deforestasi: '#eda100',
    s1: '#2a78d6',
    s2: '#eb6834',
    s3: '#1baf7a',
    naik: '#2a78d6',
    turun: '#e34948',
    redup: '#d6d5ce',
    abu: '#a9a79e',
    permukaan: '#fcfcfb',
    tinta: '#1b1d1a',
    tinta2: '#52514e',
    tinta3: '#898781',
    kisi: '#e1e0d9',
    sumbu: '#c3c2b7',
};

// ------------------------------------------------------------------ format angka Indonesia
const fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
const fmt2 = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
const ringkas = (v) => {
    if (v === null || v === undefined) return '–';
    const a = Math.abs(v);
    if (a >= 1e6) return fmt.format(v / 1e6) + ' jt';
    if (a >= 1e3) return fmt.format(v / 1e3) + ' rb';
    return fmt.format(v);
};

// ------------------------------------------------------------------ gaya dasar
Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
Chart.defaults.font.size = 11.5;
Chart.defaults.color = W.tinta3;
Chart.defaults.borderColor = W.kisi;
Chart.defaults.maintainAspectRatio = false;
Chart.defaults.animation.duration = 500;
Chart.defaults.plugins.legend.position = 'top';
Chart.defaults.plugins.legend.align = 'start';
Object.assign(Chart.defaults.plugins.legend.labels, { usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 10, boxHeight: 10, color: W.tinta2, padding: 14 });
Object.assign(Chart.defaults.plugins.tooltip, {
    backgroundColor: '#1b1d1a', titleColor: '#fff', bodyColor: '#f0efec', padding: 10, cornerRadius: 8,
    boxPadding: 4, usePointStyle: true, titleFont: { weight: '600' },
});
Chart.defaults.scale.grid.color = W.kisi;
Chart.defaults.scale.border.color = W.sumbu;
Chart.defaults.elements.bar.borderRadius = 4;
Chart.defaults.elements.line.borderWidth = 2;
Object.assign(Chart.defaults.elements.point, { radius: 4, hoverRadius: 6, borderWidth: 2, borderColor: W.permukaan, hitRadius: 12 });

const sumbuRingkas = { ticks: { callback: (v) => ringkas(v) }, grid: { color: W.kisi } };
const tanpaKisi = { grid: { display: false } };
const labelTooltip = (satuan) => (c) => {
    const v = c.chart.options.indexAxis === 'y' ? c.parsed.x : c.parsed.y;
    return ` ${c.dataset.label ? c.dataset.label + ': ' : ''}${fmt.format(v)}${satuan ? ' ' + satuan : ''}`;
};

// Batang: ujung data bulat, pangkal (di garis dasar) rata, dan tipis
const gayaBatang = { borderRadius: 4, borderSkipped: 'start', maxBarThickness: 34, categoryPercentage: 0.75, barPercentage: 0.85 };
// Segmen bertumpuk: celah 2px warna permukaan, bukan garis tepi
const gayaTumpuk = { ...gayaBatang, borderColor: W.permukaan, borderWidth: { right: 2, top: 2 }, borderRadius: 0 };

// ------------------------------------------------------------------ plugin label langsung (selektif)
const labelUjungBatang = {
    id: 'labelUjungBatang',
    afterDatasetsDraw(chart, _args, opsi) {
        if (!opsi?.aktif) return;
        const { ctx } = chart;
        ctx.save();
        ctx.font = `500 11px ${Chart.defaults.font.family}`;
        ctx.fillStyle = W.tinta2;
        ctx.textBaseline = 'middle';
        const terakhir = chart.data.datasets.length - 1;
        chart.getDatasetMeta(terakhir).data.forEach((el, i) => {
            const total = chart.data.datasets.reduce((s, ds) => s + (ds.data[i] ?? 0), 0);
            const nilai = opsi.total ? total : chart.data.datasets[terakhir].data[i];
            if (nilai === null || nilai === undefined) return;
            const horizontal = chart.options.indexAxis === 'y';
            const teks = (opsi.format ?? ringkas)(nilai);
            if (horizontal) {
                ctx.textAlign = nilai < 0 ? 'right' : 'left';
                ctx.fillText(teks, el.x + (nilai < 0 ? -6 : 6), el.y);
            } else {
                ctx.textAlign = 'center';
                ctx.fillText(teks, el.x, el.y - 10);
            }
        });
        ctx.restore();
    },
};

const labelUjungGaris = {
    id: 'labelUjungGaris',
    afterDatasetsDraw(chart, _args, opsi) {
        if (!opsi?.aktif) return;
        const { ctx } = chart;
        ctx.save();
        ctx.font = `600 11px ${Chart.defaults.font.family}`;
        ctx.textBaseline = 'middle';
        chart.data.datasets.forEach((ds, i) => {
            const meta = chart.getDatasetMeta(i);
            if (meta.hidden) return;
            const idx = ds.data.map((v, j) => (v === null ? -1 : j)).filter((j) => j >= 0).pop();
            const el = meta.data[idx];
            if (!el) return;
            ctx.fillStyle = W.tinta2;
            ctx.textAlign = 'left';
            ctx.fillText(opsi.teks ? opsi.teks(ds, ds.data[idx]) : ds.label, el.x + 9, el.y);
        });
        ctx.restore();
    },
};

const garisAcuan = {
    id: 'garisAcuan',
    beforeDatasetsDraw(chart, _args, opsi) {
        if (opsi?.nilai === undefined) return;
        const horizontal = chart.options.indexAxis === 'y';
        const skala = horizontal ? chart.scales.x : chart.scales.y;
        const p = skala.getPixelForValue(opsi.nilai);
        const { ctx, chartArea: a } = chart;
        ctx.save();
        ctx.strokeStyle = W.abu;
        ctx.lineWidth = 1.25;
        ctx.beginPath();
        if (horizontal) { ctx.moveTo(p, a.top); ctx.lineTo(p, a.bottom); } else { ctx.moveTo(a.left, p); ctx.lineTo(a.right, p); }
        ctx.stroke();
        ctx.restore();
    },
};

Chart.register(labelUjungBatang, labelUjungGaris, garisAcuan);

// ------------------------------------------------------------------ tabel pendamping
const tabelDari = (chart) => {
    const ds = chart.data.datasets;
    const sel = (v) => `<td class="angka">${v === null || v === undefined ? '–' : fmt2.format(v)}</td>`;
    if (chart.config.type === 'bubble') {
        const baris = ds.flatMap((d) => d.data.map((p) => `<tr><td>${p.nama}</td><td>${d.label}</td>${sel(p.x)}${sel(p.y)}${sel(p.kayu)}</tr>`));
        return `<table class="tabel-data"><thead><tr><th>Provinsi</th><th>Kelompok</th><th class="angka">Karhutla (ha)</th>
            <th class="angka">Deforestasi (ha)</th><th class="angka">Kayu (m³)</th></tr></thead><tbody>${baris.join('')}</tbody></table>`;
    }
    const kepala = ds.map((d) => `<th class="angka">${d.label || 'Nilai'}</th>`).join('');
    const baris = chart.data.labels.map((l, i) => `<tr><td>${l}</td>${ds.map((d) => sel(d.data[i])).join('')}</tr>`);
    return `<table class="tabel-data"><thead><tr><th></th>${kepala}</tr></thead><tbody>${baris.join('')}</tbody></table>`;
};

const grafik = {};
const buat = (id, config) => {
    grafik[id] = new Chart(document.getElementById(id), config);
    return grafik[id];
};

document.querySelectorAll('details.lihat-tabel').forEach((det) => {
    det.addEventListener('toggle', () => {
        if (det.open) det.querySelector('.isi').innerHTML = tabelDari(grafik[det.dataset.grafik]);
    });
});

// ================================================================== 1. Kawasan hutan (D1)
const kw = D.kawasan;
buat('chartKawasanProvinsi', {
    type: 'bar',
    data: {
        labels: kw.provinsi.map((p) => p.nama),
        datasets: [
            { label: 'Lindung', data: kw.provinsi.map((p) => p.lindung), backgroundColor: W.s1, ...gayaTumpuk },
            { label: 'Konservasi', data: kw.provinsi.map((p) => p.konservasi), backgroundColor: W.s2, ...gayaTumpuk },
            { label: 'Produksi', data: kw.provinsi.map((p) => p.produksi), backgroundColor: W.s3, ...gayaTumpuk,
                borderRadius: { topRight: 4, bottomRight: 4 } },
        ],
    },
    options: {
        indexAxis: 'y',
        scales: { x: { stacked: true, ...sumbuRingkas }, y: { stacked: true, ...tanpaKisi, ticks: { autoSkip: false, font: { size: 10.5 } } } },
        plugins: { tooltip: { mode: 'index', callbacks: { label: labelTooltip('ha') } } },
    },
});

const fungsi = Object.entries(kw.fungsi).sort((a, b) => b[1] - a[1]);
const totalFungsi = fungsi.reduce((s, [, v]) => s + v, 0);
buat('chartKawasanFungsi', {
    type: 'bar',
    data: {
        labels: fungsi.map(([k]) => k),
        datasets: [{ label: 'Luas (ha)', data: fungsi.map(([, v]) => v), backgroundColor: W.s1, ...gayaBatang }],
    },
    options: {
        indexAxis: 'y',
        layout: { padding: { right: 112 } },
        scales: { x: { ...sumbuRingkas, display: false }, y: { ...tanpaKisi } },
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: labelTooltip('ha') } },
            labelUjungBatang: { aktif: true, format: (v) => `${ringkas(v)} · ${fmt.format((v / totalFungsi) * 100)}%` },
        },
    },
});

// ================================================================== 2. Tutupan hutan (D2)
const tt = D.tutupan;
const hutanPerTahun = Object.fromEntries(tt.nasional.map((r) => [r.tahun, r]));
buat('chartTutupanTren', {
    type: 'line',
    data: {
        labels: tt.tahun,
        datasets: [{
            label: 'Tutupan hutan (ha)',
            data: tt.tahun.map((t) => hutanPerTahun[t]?.hutan ?? null),
            borderColor: W.tutupan, backgroundColor: W.tutupan, spanGaps: false, tension: 0,
        }],
    },
    options: {
        layout: { padding: { right: 72, top: 8 } },
        scales: { y: { ...sumbuRingkas, grace: '20%' }, x: tanpaKisi },
        plugins: {
            legend: { display: false },
            tooltip: {
                mode: 'index', intersect: false,
                callbacks: { label: (c) => ` ${ringkas(c.parsed.y)} ha · ${fmt.format(hutanPerTahun[c.label].persen)}% daratan` },
            },
            labelUjungGaris: { aktif: true, teks: (_ds, v) => `${ringkas(v)} ha` },
        },
    },
});

const pr = tt.perubahan;
buat('chartTutupanProvinsi', {
    type: 'bar',
    data: {
        labels: pr.map((p) => p.nama),
        datasets: [{ label: 'Perubahan (ha)', data: pr.map((p) => p.selisih), backgroundColor: pr.map((p) => (p.selisih < 0 ? W.turun : W.naik)), ...gayaBatang, borderSkipped: false }],
    },
    options: {
        indexAxis: 'y',
        layout: { padding: { left: 8, right: 56 } },
        scales: {
            // ruang ekstra di kiri agar label angka negatif tidak menabrak nama provinsi
            x: { ...sumbuRingkas, suggestedMin: Math.min(...pr.map((p) => p.selisih)) * 1.6 },
            y: { ...tanpaKisi, ticks: { autoSkip: false } },
        },
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip('ha') } }, garisAcuan: { nilai: 0 }, labelUjungBatang: { aktif: true } },
    },
});

// ================================================================== 3. Deforestasi (D5)
const df = D.deforestasi;
buat('chartDeforTren', {
    type: 'bar',
    data: {
        labels: df.nasional.map((r) => r.periode),
        datasets: [
            { label: 'Di dalam kawasan hutan', data: df.nasional.map((r) => r.dalam), backgroundColor: W.deforestasi, ...gayaTumpuk, borderWidth: { top: 2 } },
            { label: 'Di luar kawasan (APL)', data: df.nasional.map((r) => r.luar), backgroundColor: W.redup, ...gayaTumpuk, borderWidth: { top: 2 },
                borderRadius: { topLeft: 4, topRight: 4 } },
        ],
    },
    options: {
        layout: { padding: { top: 16 } },
        scales: { x: { stacked: true, ...tanpaKisi }, y: { stacked: true, ...sumbuRingkas } },
        plugins: { tooltip: { mode: 'index', callbacks: { label: labelTooltip('ha/th') } }, labelUjungBatang: { aktif: true, total: true } },
    },
});
buat('chartDeforProvinsi', {
    type: 'bar',
    data: { labels: df.top.map((p) => p.nama), datasets: [{ label: 'Deforestasi netto (ha)', data: df.top.map((p) => p.total), backgroundColor: W.deforestasi, ...gayaBatang }] },
    options: {
        indexAxis: 'y',
        layout: { padding: { right: 56 } },
        scales: { x: { ...sumbuRingkas }, y: { ...tanpaKisi, ticks: { autoSkip: false } } },
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip('ha') } }, labelUjungBatang: { aktif: true } },
    },
});

// ================================================================== 4 & 5. Karhutla (D3) dan produksi kayu (D4)
const trenDanPeringkat = ({ data, idTren, idPeringkat, idPilih, warna, satuan, sorot }) => {
    const tahun = Object.keys(data.nasional);
    buat(idTren, {
        type: 'bar',
        data: {
            labels: tahun,
            datasets: [{
                label: satuan, data: Object.values(data.nasional),
                backgroundColor: tahun.map((t) => (!sorot || sorot.includes(Number(t)) ? warna : W.redup)), ...gayaBatang,
            }],
        },
        options: {
            layout: { padding: { top: 18 } },
            scales: { y: sumbuRingkas, x: tanpaKisi },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip(satuan) } }, labelUjungBatang: { aktif: true } },
        },
    });

    const peringkat = buat(idPeringkat, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: satuan, data: [], backgroundColor: warna, ...gayaBatang }] },
        options: {
            indexAxis: 'y',
            layout: { padding: { right: 56 } },
            scales: { x: sumbuRingkas, y: { ...tanpaKisi, ticks: { autoSkip: false } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip(satuan) } }, labelUjungBatang: { aktif: true } },
        },
    });

    const pilih = document.getElementById(idPilih);
    const perbarui = () => {
        const i = data.tahun.indexOf(Number(pilih.value));
        const urut = data.provinsi.map((p) => ({ nama: p.nama, v: p.nilai[i] })).sort((a, b) => b.v - a.v).slice(0, 10);
        peringkat.data.labels = urut.map((p) => p.nama);
        peringkat.data.datasets[0].data = urut.map((p) => p.v);
        peringkat.data.datasets[0].label = `${satuan} (${pilih.value})`;
        peringkat.update();
    };
    pilih.addEventListener('change', perbarui);
    perbarui();
};
trenDanPeringkat({ data: D.karhutla, idTren: 'chartKarhutlaTren', idPeringkat: 'chartKarhutlaProvinsi', idPilih: 'pilihTahunKarhutla',
    warna: W.karhutla, satuan: 'ha', sorot: D.karhutla.sorot });
trenDanPeringkat({ data: D.kayu, idTren: 'chartKayuTren', idPeringkat: 'chartKayuProvinsi', idPilih: 'pilihTahunKayu',
    warna: W.kayu, satuan: 'm³' });

// ================================================================== G1. Indeks gabungan
const ix = D.indeks;
const warnaIndeks = [W.tutupan, W.karhutla, W.kayu, W.deforestasi];
buat('chartIndeks', {
    type: 'line',
    data: {
        labels: ix.tahun,
        datasets: Object.entries(ix.deret).map(([label, data], i) => ({
            label, data, borderColor: warnaIndeks[i], backgroundColor: warnaIndeks[i], tension: 0, spanGaps: false,
        })),
    },
    options: {
        layout: { padding: { right: 150 } },
        interaction: { mode: 'index', intersect: false },
        scales: { y: { beginAtZero: true, grace: '5%', title: { display: true, text: 'indeks (2018 = 100)', color: W.tinta3 } }, x: tanpaKisi },
        plugins: {
            tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${c.parsed.y === null ? '–' : fmt.format(c.parsed.y)}` } },
            garisAcuan: { nilai: 100 },
            labelUjungGaris: { aktif: true, teks: (ds, v) => `${ds.label.replace(/ \(D\d\)/, '')} ${fmt.format(v)}` },
        },
    },
});

// ================================================================== G2. Kawasan vs tutupan
const kt = D.kawasanVsTutupan;
buat('chartKawasanVsTutupan', {
    type: 'bar',
    data: {
        labels: kt.provinsi.map((p) => p.nama),
        datasets: [
            { label: 'Kawasan hutan (D1)', data: kt.provinsi.map((p) => p.kawasan), backgroundColor: W.redup, ...gayaBatang },
            { label: 'Tutupan hutan (D2)', data: kt.provinsi.map((p) => p.hutan), backgroundColor: W.tutupan, ...gayaBatang },
        ],
    },
    options: {
        indexAxis: 'y',
        scales: { x: sumbuRingkas, y: { ...tanpaKisi, ticks: { autoSkip: false } } },
        plugins: { tooltip: { mode: 'index', callbacks: { label: labelTooltip('ha') } } },
    },
});

// ================================================================== G3. Benang merah (gelembung)
const bm = D.benangMerah;
const warnaKelompok = { Sumatera: W.s1, Kalimantan: W.s2, 'Wilayah lain': W.abu };
const maksKayu = Math.max(...bm.titik.map((t) => t.kayu));
buat('chartBenangMerah', {
    type: 'bubble',
    data: {
        datasets: bm.wilayah.map((w) => ({
            label: w,
            backgroundColor: warnaKelompok[w] + 'cc',
            borderColor: W.permukaan,
            borderWidth: 2,
            hoverBorderColor: W.tinta,
            data: bm.titik.filter((t) => t.wilayah === w).map((t) => ({
                x: t.karhutla, y: t.deforestasi, r: 5 + 22 * Math.sqrt(t.kayu / maksKayu), nama: t.nama, kayu: t.kayu,
            })),
        })),
    },
    options: {
        layout: { padding: { right: 20 } },
        scales: {
            x: { ...sumbuRingkas, min: 0, grace: '8%', title: { display: true, text: 'Total karhutla 2018–2022 (ha)', color: W.tinta3 } },
            y: { ...sumbuRingkas, grace: '12%', title: { display: true, text: 'Total deforestasi netto 2018–2022 (ha)', color: W.tinta3 } },
        },
        plugins: {
            tooltip: {
                callbacks: {
                    title: (items) => items[0].raw.nama,
                    label: (c) => [` Karhutla: ${ringkas(c.raw.x)} ha`, ` Deforestasi: ${ringkas(c.raw.y)} ha`, ` Kayu: ${ringkas(c.raw.kayu)} m³`],
                },
            },
        },
    },
    plugins: [{
        id: 'namaTitik',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            ctx.save();
            ctx.font = `500 11px ${Chart.defaults.font.family}`;
            ctx.fillStyle = W.tinta2;
            ctx.textBaseline = 'middle';
            // Label hanya untuk titik yang menonjol; geser ke bawah bila menabrak label lain
            const terpakai = [];
            chart.data.datasets.forEach((ds, i) => {
                chart.getDatasetMeta(i).data.forEach((el, j) => {
                    const p = ds.data[j];
                    if (!(p.x > 200000 || p.y > 50000)) return;
                    const x = el.x + el.options.radius + 5;
                    let y = el.y;
                    const lebar = ctx.measureText(p.nama).width;
                    while (terpakai.some((b) => x < b.x + b.w && x + lebar > b.x && Math.abs(y - b.y) < 13)) y += 13;
                    terpakai.push({ x, y, w: lebar });
                    ctx.fillText(p.nama, x, y);
                });
            });
            ctx.restore();
        },
    }],
});

// ------------------------------------------------------------------ sorot menu sesuai bagian yang sedang dibaca
const tautan = [...document.querySelectorAll('.nav-tautan')];
const pengamat = new IntersectionObserver((entri) => {
    entri.filter((e) => e.isIntersecting).forEach((e) => {
        tautan.forEach((a) => a.classList.toggle('aktif', a.dataset.jangkar === e.target.id));
    });
}, { rootMargin: '-30% 0px -60% 0px' });
document.querySelectorAll('main section[id]').forEach((s) => pengamat.observe(s));
