# Sumber: user story BARU US-09, Table B.10 (fitur Sprint 2). Belum ada di SRS LokerLens Appendix B, perlu ditambahkan ke SRS.
Feature: Dashboard Analis
  As a Analis, I want to melihat ringkasan beban kerja dan antrean lowongan berisiko tinggi,
  so that saya dapat memprioritaskan lowongan yang perlu ditinjau.

  Scenario: Menampilkan dashboard Analis
    Given I am logged in as "Analis"
    When I follow "Dashboard"
    Then I should see "Dashboard Analis"
    And I should see "Pantau beban kerja dan prioritaskan lowongan berisiko tinggi."

  Scenario: Membuka antrean tinjauan dari dashboard
    Given I am logged in as "Analis"
    And I am on the "/dashboard-analis" page
    When I follow "Lihat seluruh antrean"
    Then I should see "Daftar Lowongan Prioritas Tinggi"

  Scenario: Periode tanpa antrean tinjauan
    Given I am logged in as "Analis"
    When I am on the "/dashboard-analis?periode=kustom&dari=2020-01-01&sampai=2020-01-07" page
    Then I should see "Belum ada antrean pada periode ini"

  Scenario: Peran lain tidak bisa membuka dashboard Analis
    Given I am logged in as "Masyarakat"
    When I am on the "/dashboard-analis" page
    Then the response status code should be 403
