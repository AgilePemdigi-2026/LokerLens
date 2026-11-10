# Sumber: user story BARU US-11, Table B.12 (fitur Sprint 2). Belum ada di SRS LokerLens Appendix B, perlu ditambahkan ke SRS.
Feature: Dashboard Kepala Bidang
  As a Kepala Bidang, I want to melihat ringkasan jumlah laporan berdasarkan status penanganan,
  so that saya dapat segera menemukan laporan yang menunggu keputusan.

  Scenario: Dashboard tampil setelah login
    Given I am logged in as "Kepala Bidang"
    Then I should be on "/dashboard-kepala-bidang"
    And I should see "Dashboard Kepala Bidang"
    And I should see "Ringkasan jumlah laporan berdasarkan status penanganan."

  Scenario: Membuka laporan yang menunggu keputusan
    Given I am logged in as "Kepala Bidang"
    And I am on the "/dashboard-kepala-bidang" page
    When I follow "Lihat semua"
    Then I should see "Daftar Laporan"
    And I should see "#LKR-2025-0842"

  Scenario: Peran lain tidak bisa membuka dashboard Kepala Bidang
    Given I am logged in as "Analis"
    When I am on the "/dashboard-kepala-bidang" page
    Then the response status code should be 403
