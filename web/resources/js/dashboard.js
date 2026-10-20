/*
 * Dashboard Kehutanan — semua interaksi & grafik (Chart.js). Data dari window.DATA (diisi DashboardController).
 *
 * Aturan visual: satu sumbu Y per grafik (satuan berbeda dibandingkan lewat indeks 2018 = 100); warna = identitas data
 * dan konsisten (tutupan biru, karhutla oranye, kayu hijau-toska, deforestasi kuning, lolos uji buta warna, dengan
 * versi khusus tema gelap); abu-abu untuk konteks; garis lurus untuk data tahunan; label langsung hanya di titik
 * penting; setiap grafik punya tabel data pendamping. Grafik baru dibuat saat panelnya terlihat (lazy).
 */
import Chart from 'chart.js/auto';

const D = window.DATA;
const html = document.documentElement;

// ================================================================== palet per tema
const TEMA = {
    terang: {
        s1: '#2a78d6', s2: '#eb6834', s3: '#1baf7a', s4: '#eda100', naik: '#2a78d6', turun: '#e34948',
        redup: '#dcdbd4', abu: '#a9a79e', permukaan: '#ffffff', tinta: '#161816', tinta2: '#4f524d', tinta3: '#82857e',
        kisi: '#ecebe6', sumbu: '#c9c8c0', tooltip: '#161816', brand: '#1d6949',
        sekuensial: ['#d6e7fc', '#94c0f2', '#4b93e6', '#256abf', '#123f7a'],
        merah: ['#fbd5d4', '#f29c9b', '#e34948', '#a82b2a'], biru: ['#94c0f2', '#256abf'], nol: '#efeee9',
    },
    gelap: {
        s1: '#3987e5', s2: '#d95926', s3: '#199e70', s4: '#c98500', naik: '#3987e5', turun: '#e66767',
        redup: '#363734', abu: '#6f6e69', permukaan: '#141514', tinta: '#f0efec', tinta2: '#c3c2b7', tinta3: '#8b8a83',
        kisi: '#242523', sumbu: '#383835', tooltip: '#2a2b29', brand: '#3fa772',
        sekuensial: ['#16304d', '#1d4c84', '#2b6cc0', '#5b9ae6', '#a9cdf6'],
        merah: ['#4a2120', '#7d302f', '#c84f4e', '#f2a3a3'], biru: ['#2b6cc0', '#a9cdf6'], nol: '#1d1e1d',
    },
};
let W = TEMA.gelap;
const warnaData = () => ({ tutupan: W.s1, karhutla: W.s2, kayu: W.s3, deforestasi: W.s4 });

// ================================================================== format angka Indonesia
const fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });
const fmt2 = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
const ringkas = (v) => {
    if (v === null || v === undefined || Number.isNaN(v)) return '–';
    const a = Math.abs(v);
    if (a >= 1e6) return fmt.format(v / 1e6) + ' jt';
    if (a >= 1e3) return fmt.format(v / 1e3) + ' rb';
    return fmt.format(v);
};

// ================================================================== gaya dasar Chart.js (diatur ulang saat tema berganti)
function terapkanGayaDasar() {
    W = TEMA[html.dataset.tema === 'terang' ? 'terang' : 'gelap'];
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.font.size = 11.5;
    Chart.defaults.color = W.tinta3;
    Chart.defaults.borderColor = W.kisi;
    Chart.defaults.maintainAspectRatio = false;
    Chart.defaults.animation.duration = 450;
    Object.assign(Chart.defaults.plugins.legend, { position: 'top', align: 'start' });
    Object.assign(Chart.defaults.plugins.legend.labels, {
        usePointStyle: true, pointStyle: 'rectRounded', boxWidth: 10, boxHeight: 10, color: W.tinta2, padding: 14,
    });
    Object.assign(Chart.defaults.plugins.tooltip, {
        backgroundColor: W.tooltip, titleColor: '#fff', bodyColor: '#e9e8e4', borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1,
        padding: 10, cornerRadius: 10, boxPadding: 4, usePointStyle: true, titleFont: { weight: '600' },
    });
    Chart.defaults.scale.grid.color = W.kisi;
    Chart.defaults.scale.border.color = W.sumbu;
    Object.assign(Chart.defaults.elements.point, { radius: 3.5, hoverRadius: 6, borderWidth: 2, borderColor: W.permukaan, hitRadius: 14 });
    Chart.defaults.elements.line.borderWidth = 2;
}

const sumbuRingkas = () => ({ ticks: { callback: (v) => ringkas(v) }, grid: { color: W.kisi } });
const tanpaKisi = { grid: { display: false } };
const labelTooltip = (satuan) => (c) => {
    const v = c.chart.options.indexAxis === 'y' ? c.parsed.x : c.parsed.y;
    return ` ${c.dataset.label ? c.dataset.label + ': ' : ''}${fmt.format(v)}${satuan ? ' ' + satuan : ''}`;
};
const gayaBatang = { borderRadius: 5, borderSkipped: 'start', maxBarThickness: 30, categoryPercentage: 0.78, barPercentage: 0.86 };
const gayaTumpuk = () => ({ ...gayaBatang, borderColor: W.permukaan, borderWidth: { right: 2, top: 2 }, borderRadius: 0 });
const gradien = (warna, kuat = 0.28) => (ctx) => {
    const { chart } = ctx;
    if (!chart.chartArea) return 'transparent';
    const g = chart.ctx.createLinearGradient(0, chart.chartArea.top, 0, chart.chartArea.bottom);
    g.addColorStop(0, warna + Math.round(kuat * 255).toString(16).padStart(2, '0'));
    g.addColorStop(1, warna + '00');
    return g;
};

// ================================================================== plugin label langsung (selektif) & garis acuan
Chart.register(
    {
        id: 'labelUjungBatang',
        afterDatasetsDraw(chart, _a, o) {
            if (!o?.aktif) return;
            const { ctx } = chart;
            ctx.save();
            ctx.font = `500 11px ${Chart.defaults.font.family}`;
            ctx.fillStyle = W.tinta2;
            ctx.textBaseline = 'middle';
            const akhir = chart.data.datasets.length - 1;
            chart.getDatasetMeta(akhir).data.forEach((el, i) => {
                const nilai = o.total ? chart.data.datasets.reduce((s, ds) => s + (ds.data[i] ?? 0), 0) : chart.data.datasets[akhir].data[i];
                if (nilai === null || nilai === undefined) return;
                const teks = (o.format ?? ringkas)(nilai);
                if (chart.options.indexAxis === 'y') {
                    ctx.textAlign = nilai < 0 ? 'right' : 'left';
                    ctx.fillText(teks, el.x + (nilai < 0 ? -6 : 6), el.y);
                } else {
                    ctx.textAlign = 'center';
                    ctx.fillText(teks, el.x, el.y - 10);
                }
            });
            ctx.restore();
        },
    },
    {
        id: 'labelUjungGaris',
        afterDatasetsDraw(chart, _a, o) {
            if (!o?.aktif) return;
            const { ctx } = chart;
            ctx.save();
            ctx.font = `600 11px ${Chart.defaults.font.family}`;
            ctx.textBaseline = 'middle';
            ctx.fillStyle = W.tinta2;
            const dipakai = [];
            chart.data.datasets.forEach((ds, i) => {
                const meta = chart.getDatasetMeta(i);
                if (meta.hidden) return;
                const idx = ds.data.map((v, j) => (v === null ? -1 : j)).filter((j) => j >= 0).pop();
                const el = meta.data[idx];
                if (!el) return;
                let y = el.y;
                while (dipakai.some((p) => Math.abs(p - y) < 13)) y += 13;
                dipakai.push(y);
                ctx.fillText(o.teks ? o.teks(ds, ds.data[idx]) : ds.label, el.x + 9, y);
            });
            ctx.restore();
        },
    },
    {
        id: 'garisAcuan',
        beforeDatasetsDraw(chart, _a, o) {
            if (o?.nilai === undefined) return;
            const horizontal = chart.options.indexAxis === 'y';
            const p = (horizontal ? chart.scales.x : chart.scales.y).getPixelForValue(o.nilai);
            const { ctx, chartArea: a } = chart;
            ctx.save();
            ctx.strokeStyle = W.abu;
            ctx.lineWidth = 1.25;
            ctx.beginPath();
            if (horizontal) { ctx.moveTo(p, a.top); ctx.lineTo(p, a.bottom); } else { ctx.moveTo(a.left, p); ctx.lineTo(a.right, p); }
            ctx.stroke();
            ctx.restore();
        },
    },
);

// ================================================================== definisi setiap grafik (id kanvas -> fungsi pembuat config)
const DEF = {};

// ---- ringkasan: sparkline
const SPARK = { tutupan: 'tutupan', deforestasi: 'deforestasi', karhutla: 'karhutla', kayu: 'kayu', kawasan: null };
Object.keys(SPARK).forEach((k) => {
    DEF[`spark-${k}`] = () => {
        const deret = D.deret[k];
        const warna = SPARK[k] ? warnaData()[SPARK[k]] : W.abu;
        return {
            type: 'line',
            data: { labels: Object.keys(deret), datasets: [{ data: Object.values(deret), borderColor: warna, backgroundColor: gradien(warna, 0.32), fill: true, tension: 0, pointRadius: 0, pointHoverRadius: 3, borderWidth: 1.75 }] },
            options: {
                animation: false,
                layout: { padding: 2 },
                // mulai dari 0 agar perubahan kecil (mis. kawasan −0,2%) tidak tampak seperti penurunan tajam
                scales: { x: { display: false }, y: { display: false, beginAtZero: true, grace: '8%' } },
                plugins: { legend: { display: false }, tooltip: { displayColors: false, callbacks: { title: (i) => i[0].label, label: (c) => ringkas(c.parsed.y) } } },
                interaction: { mode: 'index', intersect: false },
            },
        };
    };
});

// ---- ringkasan: indeks gabungan
DEF.chartIndeks = () => {
    const ix = D.indeks;
    const warna = Object.values(warnaData());
    return {
        type: 'line',
        data: {
            labels: ix.tahun,
            datasets: Object.entries(ix.deret).map(([label, data], i) => ({ label, data, borderColor: warna[i], backgroundColor: warna[i], tension: 0, spanGaps: false })),
        },
        options: {
            layout: { padding: { right: 128 } },
            interaction: { mode: 'index', intersect: false },
            scales: { y: { beginAtZero: true, grace: '5%', title: { display: true, text: 'indeks (2018 = 100)', color: W.tinta3 } }, x: tanpaKisi },
            plugins: {
                tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${c.parsed.y === null ? '–' : fmt.format(c.parsed.y)}` } },
                garisAcuan: { nilai: 100 },
                labelUjungGaris: { aktif: true, teks: (ds, v) => `${ds.label.replace(/ \(D\d\)/, '')} ${fmt.format(v)}` },
            },
        },
    };
};

// ---- D1 kawasan
DEF.chartKawasanProvinsi = () => {
    const kw = D.kawasan;
    return {
        type: 'bar',
        data: {
            labels: kw.provinsi.map((p) => p.nama),
            datasets: [
                { label: 'Lindung', data: kw.provinsi.map((p) => p.lindung), backgroundColor: W.s1, ...gayaTumpuk() },
                { label: 'Konservasi', data: kw.provinsi.map((p) => p.konservasi), backgroundColor: W.s2, ...gayaTumpuk() },
                { label: 'Produksi', data: kw.provinsi.map((p) => p.produksi), backgroundColor: W.s3, ...gayaTumpuk(), borderRadius: { topRight: 5, bottomRight: 5 } },
            ],
        },
        options: {
            indexAxis: 'y',
            scales: { x: { stacked: true, ...sumbuRingkas() }, y: { stacked: true, ...tanpaKisi, ticks: { autoSkip: false, font: { size: 10.5 } } } },
            plugins: { tooltip: { mode: 'index', callbacks: { label: labelTooltip('ha') } } },
        },
    };
};
DEF.chartKawasanFungsi = () => {
    const fungsi = Object.entries(D.kawasan.fungsi).sort((a, b) => b[1] - a[1]);
    const total = fungsi.reduce((s, [, v]) => s + v, 0);
    return {
        type: 'bar',
        data: { labels: fungsi.map(([k]) => k), datasets: [{ label: 'Luas (ha)', data: fungsi.map(([, v]) => v), backgroundColor: W.s1, ...gayaBatang }] },
        options: {
            indexAxis: 'y',
            layout: { padding: { right: 112 } },
            scales: { x: { display: false }, y: tanpaKisi },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: labelTooltip('ha') } },
                labelUjungBatang: { aktif: true, format: (v) => `${ringkas(v)} · ${fmt.format((v / total) * 100)}%` },
            },
        },
    };
};

// ---- D2 tutupan
DEF.chartTutupanTren = () => {
    const tt = D.tutupan;
    const perTahun = Object.fromEntries(tt.nasional.map((r) => [r.tahun, r]));
    const warna = warnaData().tutupan;
    return {
        type: 'line',
        data: { labels: tt.tahun, datasets: [{ label: 'Tutupan hutan (ha)', data: tt.tahun.map((t) => perTahun[t]?.hutan ?? null), borderColor: warna, backgroundColor: gradien(warna), fill: 'start', spanGaps: false, tension: 0, pointBackgroundColor: warna }] },
        options: {
            layout: { padding: { right: 72, top: 8 } },
            scales: { y: { ...sumbuRingkas(), grace: '25%' }, x: tanpaKisi },
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false, callbacks: { label: (c) => ` ${ringkas(c.parsed.y)} ha · ${fmt.format(perTahun[c.label].persen)}% daratan` } },
                labelUjungGaris: { aktif: true, teks: (_d, v) => `${ringkas(v)} ha` },
            },
        },
    };
};
DEF.chartTutupanProvinsi = () => {
    const pr = D.tutupan.perubahan;
    return {
        type: 'bar',
        data: { labels: pr.map((p) => p.nama), datasets: [{ label: 'Perubahan (ha)', data: pr.map((p) => p.selisih), backgroundColor: pr.map((p) => (p.selisih < 0 ? W.turun : W.naik)), ...gayaBatang, borderSkipped: false }] },
        options: {
            indexAxis: 'y',
            layout: { padding: { left: 8, right: 56 } },
            scales: { x: { ...sumbuRingkas(), suggestedMin: Math.min(...pr.map((p) => p.selisih)) * 1.6 }, y: { ...tanpaKisi, ticks: { autoSkip: false } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip('ha') } }, garisAcuan: { nilai: 0 }, labelUjungBatang: { aktif: true } },
        },
    };
};

// ---- D5 deforestasi
DEF.chartDeforTren = () => {
    const df = D.deforestasi;
    return {
        type: 'bar',
        data: {
            labels: df.nasional.map((r) => r.periode),
            datasets: [
                { label: 'Di dalam kawasan hutan', data: df.nasional.map((r) => r.dalam), backgroundColor: W.s4, ...gayaTumpuk(), borderWidth: { top: 2 } },
                { label: 'Di luar kawasan (APL)', data: df.nasional.map((r) => r.luar), backgroundColor: W.redup, ...gayaTumpuk(), borderWidth: { top: 2 }, borderRadius: { topLeft: 5, topRight: 5 } },
            ],
        },
        options: {
            layout: { padding: { top: 16 } },
            scales: { x: { stacked: true, ...tanpaKisi }, y: { stacked: true, ...sumbuRingkas() } },
            plugins: { tooltip: { mode: 'index', callbacks: { label: labelTooltip('ha/th') } }, labelUjungBatang: { aktif: true, total: true } },
        },
    };
};
DEF.chartDeforProvinsi = () => {
    const top = D.deforestasi.top;
    return {
        type: 'bar',
        data: { labels: top.map((p) => p.nama), datasets: [{ label: 'Deforestasi netto (ha)', data: top.map((p) => p.total), backgroundColor: W.s4, ...gayaBatang }] },
        options: {
            indexAxis: 'y',
            layout: { padding: { right: 56 } },
            scales: { x: sumbuRingkas(), y: { ...tanpaKisi, ticks: { autoSkip: false } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip('ha') } }, labelUjungBatang: { aktif: true } },
        },
    };
};

// ---- D3 karhutla & D4 kayu: tren nasional + peringkat provinsi per tahun
const trenNasional = (data, warna, satuan, sorot) => () => {
    const tahun = Object.keys(data.nasional);
    return {
        type: 'bar',
        data: { labels: tahun, datasets: [{ label: satuan, data: Object.values(data.nasional), backgroundColor: tahun.map((t) => (!sorot || sorot.includes(Number(t)) ? warna() : W.redup)), ...gayaBatang }] },
        options: {
            layout: { padding: { top: 18 } },
            scales: { y: sumbuRingkas(), x: tanpaKisi },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip(satuan) } }, labelUjungBatang: { aktif: true } },
        },
    };
};
const peringkatProvinsi = (data, warna, satuan, idPilih) => () => {
    const tahunDipilih = Number(document.getElementById(idPilih).value);
    const i = data.tahun.indexOf(tahunDipilih);
    const urut = data.provinsi.map((p) => ({ nama: p.nama, v: p.nilai[i] })).sort((a, b) => b.v - a.v).slice(0, 10);
    return {
        type: 'bar',
        data: { labels: urut.map((p) => p.nama), datasets: [{ label: `${satuan} (${tahunDipilih})`, data: urut.map((p) => p.v), backgroundColor: warna(), ...gayaBatang }] },
        options: {
            indexAxis: 'y',
            layout: { padding: { right: 56 } },
            scales: { x: sumbuRingkas(), y: { ...tanpaKisi, ticks: { autoSkip: false } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip(satuan) } }, labelUjungBatang: { aktif: true } },
        },
    };
};
DEF.chartKarhutlaTren = trenNasional(D.karhutla, () => warnaData().karhutla, 'ha', D.karhutla.sorot);
DEF.chartKarhutlaProvinsi = peringkatProvinsi(D.karhutla, () => warnaData().karhutla, 'ha', 'pilihTahunKarhutla');
DEF.chartKayuTren = trenNasional(D.kayu, () => warnaData().kayu, 'm³');
DEF.chartKayuProvinsi = peringkatProvinsi(D.kayu, () => warnaData().kayu, 'm³', 'pilihTahunKayu');

// ---- G2 kawasan vs tutupan
DEF.chartKawasanVsTutupan = () => {
    const kt = D.kawasanVsTutupan;
    return {
        type: 'bar',
        data: {
            labels: kt.provinsi.map((p) => p.nama),
            datasets: [
                { label: 'Kawasan hutan (D1)', data: kt.provinsi.map((p) => p.kawasan), backgroundColor: W.redup, ...gayaBatang },
                { label: 'Tutupan hutan (D2)', data: kt.provinsi.map((p) => p.hutan), backgroundColor: warnaData().tutupan, ...gayaBatang },
            ],
        },
        options: {
            indexAxis: 'y',
            scales: { x: sumbuRingkas(), y: { ...tanpaKisi, ticks: { autoSkip: false } } },
            plugins: { tooltip: { mode: 'index', callbacks: { label: labelTooltip('ha') } } },
        },
    };
};

// ---- G3 benang merah (gelembung)
DEF.chartBenangMerah = () => {
    const bm = D.benangMerah;
    const warnaKelompok = { Sumatera: W.s1, Kalimantan: W.s2, 'Wilayah lain': W.abu };
    const maks = Math.max(...bm.titik.map((t) => t.kayu));
    return {
        type: 'bubble',
        data: {
            datasets: bm.wilayah.map((w) => ({
                label: w, backgroundColor: warnaKelompok[w] + 'd0', borderColor: W.permukaan, borderWidth: 2, hoverBorderColor: W.tinta,
                data: bm.titik.filter((t) => t.wilayah === w).map((t) => ({ x: t.karhutla, y: t.deforestasi, r: 5 + 22 * Math.sqrt(t.kayu / maks), nama: t.nama, kayu: t.kayu })),
            })),
        },
        options: {
            layout: { padding: { right: 20 } },
            scales: {
                x: { ...sumbuRingkas(), min: 0, grace: '8%', title: { display: true, text: 'Total karhutla 2018–2022 (ha)', color: W.tinta3 } },
                y: { ...sumbuRingkas(), grace: '12%', title: { display: true, text: 'Total deforestasi netto 2018–2022 (ha)', color: W.tinta3 } },
            },
            plugins: {
                tooltip: { callbacks: { title: (it) => it[0].raw.nama, label: (c) => [` Karhutla: ${ringkas(c.raw.x)} ha`, ` Deforestasi: ${ringkas(c.raw.y)} ha`, ` Kayu: ${ringkas(c.raw.kayu)} m³`] } },
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
                const dipakai = [];
                chart.data.datasets.forEach((ds, i) => chart.getDatasetMeta(i).data.forEach((el, j) => {
                    const p = ds.data[j];
                    if (!(p.x > 200000 || p.y > 50000)) return;
                    const x = el.x + el.options.radius + 5;
                    const w = ctx.measureText(p.nama).width;
                    let y = el.y;
                    while (dipakai.some((b) => x < b.x + b.w && x + w > b.x && Math.abs(y - b.y) < 13)) y += 13;
                    dipakai.push({ x, y, w });
                    ctx.fillText(p.nama, x, y);
                }));
                ctx.restore();
            },
        }],
    };
};

// ---- detail provinsi di peta sebaran
DEF.chartDetailProvinsi = () => {
    const ds = D.sebaran.dataset[peta.data];
    const label = Object.keys(ds.nilai);
    const nilai = label.map((t) => ds.nilai[t][peta.kode] ?? null);
    const warna = W.s1;
    return {
        type: 'bar',
        data: { labels: label, datasets: [{ label: ds.satuan, data: nilai, backgroundColor: label.map((t) => (t === peta.tahun ? warna : W.redup)), ...gayaBatang, borderSkipped: false }] },
        options: {
            scales: { y: { ...sumbuRingkas(), grace: '10%' }, x: { ...tanpaKisi, ticks: { font: { size: 10 }, maxRotation: 0, autoSkip: false, callback(v) { const l = this.getLabelForValue(v); return l.length > 4 ? l.replace(/^20(\d\d)-20(\d\d)$/, '$1–$2') : l; } } } },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: labelTooltip(ds.satuan) } } },
        },
    };
};

// ================================================================== pembuatan grafik secara lazy
const grafik = {};
const terlihat = (el) => el && el.offsetParent !== null;
function pastikanGrafik(akar = document) {
    akar.querySelectorAll('canvas[id]').forEach((cv) => {
        if (grafik[cv.id] || !DEF[cv.id] || !terlihat(cv)) return;
        grafik[cv.id] = new Chart(cv, DEF[cv.id]());
    });
}
function bangunUlang(id) {
    if (grafik[id]) { grafik[id].destroy(); delete grafik[id]; }
    pastikanGrafik(document.getElementById(id)?.parentElement ?? document);
}
function bangunUlangSemua() {
    Object.keys(grafik).forEach((id) => { grafik[id].destroy(); delete grafik[id]; });
    pastikanGrafik();
}

// Tabel pendamping
const tabelDari = (chart) => {
    const ds = chart.data.datasets;
    const sel = (v) => `<td class="angka">${v === null || v === undefined ? '–' : fmt2.format(v)}</td>`;
    if (chart.config.type === 'bubble') {
        const baris = ds.flatMap((d) => d.data.map((p) => `<tr><td>${p.nama}</td><td>${d.label}</td>${sel(p.x)}${sel(p.y)}${sel(p.kayu)}</tr>`));
        return `<table class="tabel-data"><thead><tr><th>Provinsi</th><th>Kelompok</th><th class="angka">Karhutla (ha)</th><th class="angka">Deforestasi (ha)</th><th class="angka">Kayu (m³)</th></tr></thead><tbody>${baris.join('')}</tbody></table>`;
    }
    const kepala = ds.map((d) => `<th class="angka">${d.label || 'Nilai'}</th>`).join('');
    const baris = chart.data.labels.map((l, i) => `<tr><td>${l}</td>${ds.map((d) => sel(d.data[i])).join('')}</tr>`);
    return `<table class="tabel-data"><thead><tr><th></th>${kepala}</tr></thead><tbody>${baris.join('')}</tbody></table>`;
};
document.querySelectorAll('details.lihat-tabel[data-grafik]').forEach((det) => det.addEventListener('toggle', () => {
    if (det.open && grafik[det.dataset.grafik]) det.querySelector('.isi').innerHTML = tabelDari(grafik[det.dataset.grafik]);
}));

// ================================================================== tab utama
const TAB = ['ringkasan', 'per-data', 'sebaran', 'gabungan', 'integrasi'];
const BAGIAN_KE_DATA = { kawasan: 'D1', tutupan: 'D2', deforestasi: 'D5', karhutla: 'D3', kayu: 'D4' };

function bukaTab(id, { simpan = true } = {}) {
    if (!TAB.includes(id)) id = 'ringkasan';
    document.querySelectorAll('[data-panel]').forEach((p) => { p.hidden = p.dataset.panel !== id; });
    document.querySelectorAll('[data-tab]').forEach((t) => t.setAttribute('aria-selected', String(t.dataset.tab === id)));
    if (simpan) history.replaceState(null, '', `#${id}`);
    window.scrollTo({ top: 0, behavior: 'instant' });
    if (id === 'sebaran') gambarPeta();
    pastikanGrafik(document.getElementById(`panel-${id}`));
}
document.querySelectorAll('[data-tab]').forEach((t) => t.addEventListener('click', () => bukaTab(t.dataset.tab)));
document.querySelectorAll('[data-ke-tab]').forEach((a) => a.addEventListener('click', (e) => { e.preventDefault(); bukaTab(a.dataset.keTab); }));

// ================================================================== per data: dropdown
const pilihData = document.getElementById('pilihData');
function tampilkanData(kode) {
    pilihData.value = kode;
    document.querySelectorAll('[data-dataset]').forEach((b) => { b.hidden = b.dataset.dataset !== kode; });
    pastikanGrafik(document.getElementById('panel-per-data'));
}
pilihData.addEventListener('change', () => tampilkanData(pilihData.value));
[['pilihTahunKarhutla', 'chartKarhutlaProvinsi'], ['pilihTahunKayu', 'chartKayuProvinsi']].forEach(([sel, id]) =>
    document.getElementById(sel).addEventListener('change', () => bangunUlang(id)));

// ================================================================== peta sebaran (tile map)
const peta = { data: null, tahun: null, kode: null };
const elPeta = document.getElementById('peta');
const elData = document.getElementById('sebaranData');
const elTahun = document.getElementById('sebaranTahun');
const satuanTeks = (v, satuan) => (satuan === '%' ? `${fmt.format(v)}%` : `${ringkas(v)} ${satuan}`);

const kuantil = (arr, q) => {
    const s = [...arr].sort((a, b) => a - b);
    const pos = (s.length - 1) * q;
    const lo = Math.floor(pos);
    return s[lo] + (s[Math.min(lo + 1, s.length - 1)] - s[lo]) * (pos - lo);
};

function skalaWarna(ds, nilaiTahun) {
    const vals = Object.values(nilaiTahun).filter((v) => v !== null && v !== undefined);
    if (ds.skala === 'divergen') {
        const pos = vals.filter((v) => v > 0);
        const neg = vals.filter((v) => v < 0).map(Math.abs);
        const batasPos = [0.25, 0.5, 0.75].map((q) => kuantil(pos, q));
        const batasNeg = neg.length ? kuantil(neg, 0.5) : 0;
        const warna = (v) => {
            if (v === 0) return W.nol;
            if (v < 0) return Math.abs(v) <= batasNeg ? W.biru[0] : W.biru[1];
            const i = batasPos.findIndex((b) => v <= b);
            return W.merah[i === -1 ? 3 : i];
        };
        const legenda = [
            ...(neg.length ? [[W.biru[1], `≤ −${ringkas(batasNeg)}`], [W.biru[0], 'reforestasi']] : []),
            [W.merah[0], `≤ ${ringkas(batasPos[0])}`], [W.merah[1], `≤ ${ringkas(batasPos[1])}`],
            [W.merah[2], `≤ ${ringkas(batasPos[2])}`], [W.merah[3], `> ${ringkas(batasPos[2])}`],
        ];
        return { warna, legenda };
    }
    const positif = vals.filter((v) => v > 0);
    const batas = [0.2, 0.4, 0.6, 0.8].map((q) => kuantil(positif, q));
    const fmtBatas = (v) => (ds.satuan === '%' ? `${fmt.format(v)}%` : ringkas(v));
    const warna = (v) => {
        if (v <= 0) return W.nol;
        const i = batas.findIndex((b) => v <= b);
        return W.sekuensial[i === -1 ? 4 : i];
    };
    const legenda = [
        ...(vals.some((v) => v <= 0) ? [[W.nol, '0']] : []),
        [W.sekuensial[0], `≤ ${fmtBatas(batas[0])}`], [W.sekuensial[1], `≤ ${fmtBatas(batas[1])}`], [W.sekuensial[2], `≤ ${fmtBatas(batas[2])}`],
        [W.sekuensial[3], `≤ ${fmtBatas(batas[3])}`], [W.sekuensial[4], `> ${fmtBatas(batas[3])}`],
    ];
    return { warna, legenda };
}

const gelapkah = (hex) => {
    const n = parseInt(hex.slice(1), 16);
    const [r, g, b] = [(n >> 16) & 255, (n >> 8) & 255, n & 255].map((c) => { c /= 255; return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4; });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b < 0.32;
};

const tooltipPeta = Object.assign(document.createElement('div'), {
    className: 'pointer-events-none fixed z-50 hidden rounded-xl border border-white/10 px-3 py-2 text-xs text-white shadow-xl',
});
document.body.append(tooltipPeta);

function isiTahun() {
    const ds = D.sebaran.dataset[peta.data];
    const daftar = Object.keys(ds.nilai);
    elTahun.innerHTML = daftar.map((t) => `<option value="${t}">${t}</option>`).join('');
    peta.tahun = daftar.includes(peta.tahun) ? peta.tahun : daftar[daftar.length - 1];
    elTahun.value = peta.tahun;
}

function gambarPeta() {
    if (!peta.data) { peta.data = elData.value; isiTahun(); }
    const ds = D.sebaran.dataset[peta.data];
    const nilaiTahun = ds.nilai[peta.tahun] ?? {};
    const { warna, legenda } = skalaWarna(ds, nilaiTahun);
    const urut = Object.entries(nilaiTahun).filter(([, v]) => v !== null).sort((a, b) => b[1] - a[1]);
    const peringkat = Object.fromEntries(urut.map(([k], i) => [k, i + 1]));

    document.getElementById('sebaranJudul').textContent = `${ds.label}, ${peta.tahun}`;
    document.getElementById('sebaranCatatan').textContent = ds.catatan;
    document.getElementById('sebaranLegenda').innerHTML = legenda
        .map(([w, l]) => `<span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-[4px] border border-garis" style="background:${w}"></span>${l}</span>`)
        .join('');

    elPeta.innerHTML = '';
    D.sebaran.provinsi.forEach((p) => {
        const v = nilaiTahun[p.kode];
        const digabung = (v === undefined || v === null) && ds.kaltaraDigabung && p.kode === '65';
        const latar = v === undefined || v === null ? W.nol : warna(v);
        // warna teks mengikuti kecerahan kotak (bukan tema) agar selalu terbaca
        const warnaTeks = gelapkah(latar) ? '#ffffff' : '#161816';
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'petak';
        b.style.cssText = `grid-column:${p.kolom + 1};grid-row:${p.baris + 1};background:${latar};color:${warnaTeks};` +
            (digabung ? `background-image:repeating-linear-gradient(135deg,transparent 0 5px,${W.sumbu} 5px 6px);` : '');
        b.setAttribute('aria-pressed', String(peta.kode === p.kode));
        b.setAttribute('aria-label', `${p.nama}: ${v === undefined || v === null ? 'tidak ada data' : satuanTeks(v, ds.satuan)}`);
        b.innerHTML = `<span class="text-[9.5px] font-bold tracking-wide opacity-90">${p.singkat}</span>
            <span class="text-[10.5px] font-medium tabular-nums opacity-95">${digabung ? '→ KALTIM' : v === undefined || v === null ? '–' : (ds.satuan === '%' ? fmt.format(v) + '%' : ringkas(v))}</span>`;
        b.addEventListener('mouseenter', () => {
            tooltipPeta.style.background = W.tooltip;
            tooltipPeta.innerHTML = `<div class="font-semibold">${p.nama}</div><div class="mt-0.5 opacity-80">${digabung ? 'Data digabung ke Kalimantan Timur'
                : v === undefined || v === null ? 'Tidak ada data' : `${satuanTeks(v, ds.satuan)} · peringkat ${peringkat[p.kode]} dari ${urut.length}`}</div>`;
            tooltipPeta.classList.remove('hidden');
        });
        b.addEventListener('mousemove', (e) => { tooltipPeta.style.left = `${e.clientX + 14}px`; tooltipPeta.style.top = `${e.clientY + 14}px`; });
        b.addEventListener('mouseleave', () => tooltipPeta.classList.add('hidden'));
        b.addEventListener('click', () => { peta.kode = p.kode; gambarPeta(); });
        elPeta.append(b);
    });

    // provinsi terpilih: bawaan = peringkat 1
    if (!peta.kode || !D.sebaran.provinsi.some((p) => p.kode === peta.kode)) peta.kode = urut[0]?.[0];
    const prov = D.sebaran.provinsi.find((p) => p.kode === peta.kode);
    const v = nilaiTahun[peta.kode];
    document.getElementById('detailNama').textContent = prov?.nama ?? '—';
    document.getElementById('detailWilayah').textContent = prov ? `Wilayah ${prov.wilayah}` : '';
    document.getElementById('detailNilai').textContent = v === undefined || v === null ? '–' : (ds.satuan === '%' ? fmt.format(v) : ringkas(v));
    document.getElementById('detailSatuan').textContent = v === undefined || v === null ? '' : ds.satuan === '%' ? '% daratan' : ds.satuan;
    document.getElementById('detailPeringkat').textContent = peringkat[peta.kode] ? `Peringkat ${peringkat[peta.kode]} dari ${urut.length} provinsi pada ${peta.tahun}` : 'Tidak ada data pada periode ini';
    elPeta.querySelectorAll('.petak').forEach((el, i) => el.setAttribute('aria-pressed', String(D.sebaran.provinsi[i].kode === peta.kode)));

    const maks = urut[0]?.[1] || 1;
    document.getElementById('top5Ket').textContent = `${ds.label}, ${peta.tahun}`;
    document.getElementById('top5').innerHTML = urut.slice(0, 5).map(([k, nilai], i) => {
        const nama = D.sebaran.provinsi.find((p) => p.kode === k)?.nama ?? k;
        return `<li class="text-[12.5px]"><div class="flex justify-between gap-2"><span class="text-ink"><span class="mr-1.5 text-ink-3">${i + 1}</span>${nama}</span>
            <span class="font-medium tabular-nums text-ink-2">${satuanTeks(nilai, ds.satuan)}</span></div>
            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-2"><div class="h-full rounded-full" style="width:${Math.max(2, (nilai / maks) * 100)}%;background:${W.s1}"></div></div></li>`;
    }).join('');

    bangunUlang('chartDetailProvinsi');
    const det = document.querySelector('[data-tabel-peta]');
    if (det.open) isiTabelPeta();
}

function isiTabelPeta() {
    const ds = D.sebaran.dataset[peta.data];
    const nilai = ds.nilai[peta.tahun] ?? {};
    const baris = D.sebaran.provinsi.map((p) => [p.nama, nilai[p.kode]]).sort((a, b) => (b[1] ?? -Infinity) - (a[1] ?? -Infinity));
    document.querySelector('[data-tabel-peta] .isi').innerHTML = `<table class="tabel-data"><thead><tr><th>Provinsi</th><th class="angka">${ds.label} (${ds.satuan})</th></tr></thead><tbody>${
        baris.map(([n, v]) => `<tr><td>${n}</td><td class="angka">${v === undefined || v === null ? '–' : fmt2.format(v)}</td></tr>`).join('')}</tbody></table>`;
}
document.querySelector('[data-tabel-peta]').addEventListener('toggle', (e) => { if (e.target.open) isiTabelPeta(); });
elData.addEventListener('change', () => { peta.data = elData.value; peta.kode = null; isiTahun(); gambarPeta(); });
elTahun.addEventListener('change', () => { peta.tahun = elTahun.value; gambarPeta(); });

// ================================================================== tema terang/gelap
document.getElementById('gantiTema').addEventListener('click', () => {
    html.dataset.tema = html.dataset.tema === 'terang' ? 'gelap' : 'terang';
    try { localStorage.setItem('tema', html.dataset.tema); } catch (e) { /* penyimpanan tidak tersedia */ }
    terapkanGayaDasar();
    bangunUlangSemua();
    if (!document.getElementById('panel-sebaran').hidden) gambarPeta();
});

// ================================================================== mulai
function ikutiHash() {
    const hash = location.hash.slice(1);
    if (BAGIAN_KE_DATA[hash]) { tampilkanData(BAGIAN_KE_DATA[hash]); bukaTab('per-data'); } else { bukaTab(hash || 'ringkasan', { simpan: false }); }
}
terapkanGayaDasar();
ikutiHash();
window.addEventListener('hashchange', ikutiHash);
