# Sumber: user story BARU US-06, Table B.7 (fitur Sprint 2, Data Mockup Gambar 2.4). Belum ada di SRS LokerLens Appendix B, perlu ditambahkan ke SRS.
Feature: Beranda
  As a Masyarakat, I want to melihat beranda berisi pintasan layanan dan pengaduan terbaru saya,
  so that saya dapat langsung melapor atau memantau pengaduan dari satu halaman.

  Scenario: Menampilkan beranda dan pengaduan terbaru
    Given I am logged in as "Masyarakat"
    When I am on the "/beranda" page
    Then I should see "Satu tempat untuk melapor, memantau, dan meninjau."
    And I should see "Pengaduan Terbaru Anda"
    And I should see "#LKR-8821"

  Scenario: Membuka Status Pengaduan dari beranda
    Given I am logged in as "Masyarakat"
    And I am on the "/beranda" page
    When I follow "Cek Status Pengaduan"
    Then I should be on "/status-pengaduan"
    And I should see "Pengaduan Berjalan"

  Scenario: Beranda akun yang belum punya pengaduan
    Given I am logged in as "Masyarakat Baru"
    When I am on the "/beranda" page
    Then I should see "Belum ada pengaduan yang diajukan"

  Scenario: Peran lain tidak bisa membuka beranda Masyarakat
    Given I am logged in as "Analis"
    When I am on the "/beranda" page
    Then the response status code should be 403
