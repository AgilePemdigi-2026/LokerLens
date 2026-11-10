# Sumber: user story BARU US-07, Table B.8 (fitur Sprint 2). Belum ada di SRS LokerLens Appendix B, perlu ditambahkan ke SRS.
Feature: Status pengaduan
  As a Masyarakat, I want to memantau status dan tahapan penanganan pengaduan yang masih berjalan,
  so that saya mengetahui perkembangan laporan yang saya ajukan.

  Scenario: Menampilkan pengaduan yang masih berjalan
    Given I am logged in as "Masyarakat"
    When I am on the "/status-pengaduan" page
    Then I should see "Pengaduan Berjalan"
    And I should see "#LKR-8821"
    And I should not see "#LKR-8798"

  Scenario: Melihat progres penanganan satu pengaduan
    Given I am logged in as "Masyarakat"
    And I am on the "/status-pengaduan" page
    When I follow "#LKR-8821"
    Then I should see "Progres Penanganan"
    And I should see "Ditinjau Analis"

  Scenario: Tidak ada pengaduan yang sedang berjalan
    Given I am logged in as "Masyarakat Baru"
    When I am on the "/status-pengaduan" page
    Then I should see "Belum ada pengaduan berjalan"

  Scenario: Peran lain tidak bisa membuka Status Pengaduan
    Given I am logged in as "Kepala Bidang"
    When I am on the "/status-pengaduan" page
    Then the response status code should be 403
