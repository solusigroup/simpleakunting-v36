<?php

class Dashboard extends Controller {
    public function index() {
        $user = Auth::user();
        $dashboardModel = $this->model('Dashboard');
        $currentTenantId = $this->tenantId();
        
        if (($user['role'] === 'Superadmin' || $user['role'] === 'Penyelia Wilayah') && $currentTenantId === null) {
            // CENTRAL DASHBOARD LOGIC
            $data['judul'] = 'Central Dashboard';
            
            if ($user['role'] === 'Penyelia Wilayah') {
                // Penyelia Wilayah: filter tenant hanya di klusternya
                $klusterId = Auth::getKlusterWilayahId();
                $data['tenants'] = $this->model('Tenants')->getTenantsByKluster($klusterId);
                $kluster = $this->model('KlusterWilayah')->getKlusterById($klusterId);
                $data['judul'] = 'Dashboard Wilayah - ' . ($kluster['nama_kabupaten'] ?? 'Kluster');
                $data['kluster_info'] = $kluster;
                
                // Summary hanya untuk tenant di kluster ini
                $data['summary'] = $dashboardModel->getCentralSummaryByKluster($klusterId);
            } else {
                // Superadmin: lihat semua
                $data['summary'] = $dashboardModel->getCentralSummary();
                $data['tenants'] = $this->model('Tenants')->getAllTenants();
            }
            
            // Tren agregat
            $trendData = $dashboardModel->getSalesPurchasesTrend(null);
            $data['chart_trend'] = $this->_prepareTrendData($trendData);

            $this->view('templates/header', $data);
            $this->view('dashboard/central', $data);
            $this->view('templates/footer');
        } else {
            // TENANT DASHBOARD LOGIC (Bisa user normal atau Superadmin yang sedang memantau tenant)
            $data['judul'] = 'Dashboard';
            if (isset($user['impersonating'])) {
                $data['judul'] .= ' - Monitoring ' . ($user['tenant_name'] ?? '');
            }
            
            $data['summary'] = $dashboardModel->getSummary($currentTenantId);
            $trendData = $dashboardModel->getSalesPurchasesTrend($currentTenantId);
            $data['chart_trend'] = $this->_prepareTrendData($trendData);

            $this->view('templates/header', $data);
            $this->view('dashboard/index', $data);
            $this->view('templates/footer');
        }
    }

    private function _prepareTrendData($trendData) {
        $labels = [];
        $salesData = [];
        $purchasesData = [];
        foreach($trendData as $row) {
            $labels[] = date('M Y', strtotime($row['periode'] . '-01'));
            $salesData[] = $row['total_penjualan'];
            $purchasesData[] = $row['total_pembelian'];
        }
        return [
            'labels' => json_encode($labels),
            'sales' => json_encode($salesData),
            'purchases' => json_encode($purchasesData)
        ];
    }
}
