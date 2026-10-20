<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(DashboardService $data)
    {
        $halaman = [
            'kpi' => $data->kpi(),
            'kawasan' => $data->kawasanHutan(),
            'tutupan' => $data->tutupanLahan(),
            'deforestasi' => $data->deforestasi(),
            'karhutla' => $data->karhutla(),
            'kayu' => $data->produksiKayu(),
            'indeks' => $data->indeksGabungan(),
            'kawasanVsTutupan' => $data->kawasanVsTutupan(),
            'benangMerah' => $data->benangMerahProvinsi(),
            'validasi' => $data->validasiTutupanVsDeforestasi(),
            'sebaran' => $data->sebaran(),
            'integrasi' => $data->statusIntegrasi(),
        ];

        // Data yang dikirim ke JavaScript (Chart.js & peta sebaran)
        $halaman['grafik'] = collect($halaman)->only([
            'kawasan', 'tutupan', 'deforestasi', 'karhutla', 'kayu', 'indeks', 'kawasanVsTutupan', 'benangMerah', 'sebaran',
        ])->put('deret', $halaman['kpi']['deret']);

        return view('dashboard.index', $halaman);
    }
}
