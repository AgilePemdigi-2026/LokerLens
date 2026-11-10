# Sumber: user story BARU US-08, Table B.9 (fitur Sprint 2). Belum ada di SRS LokerLens Appendix B, perlu ditambahkan ke SRS.
Feature: Riwayat pengaduan
  As a Masyarakat, I want to melihat kembali pengaduan yang sudah selesai beserta hasil akhirnya,
  so that saya dapat meninjau hasil pemeriksaan dan keputusan atas laporan saya.

  Scenario: Menampilkan pengaduan yang sudah selesai
    Given I am logged in as "Masyarakat"
    When I am on the "/riwayat-pengaduan" page
    Then I should see "Total Selesai"
    And I should see "#LKR-8798"
    And I should see "#LKR-8752"
    And I should not see "#LKR-8821"

  Scenario: Memfilter riwayat berdasarkan status akhir
    Given I am logged in as "Masyarakat"
    And I am on the "/riwayat-pengaduan" page
    When I select "Tidak Terbukti" from "status"
    And I press "Cari"
    Then I should see "#LKR-8752"
    And I should not see "#LKR-8798"

  Scenario: Belum ada pengaduan yang selesai
    Given I am logged in as "Masyarakat Baru"
    When I am on the "/riwayat-pengaduan" page
    Then I should see "Belum ada pengaduan yang selesai"

  Scenario: Peran lain tidak bisa membuka Riwayat Pengaduan
    Given I am logged in as "Analis"
    When I am on the "/riwayat-pengaduan" page
    Then the response status code should be 403
