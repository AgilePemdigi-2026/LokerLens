# Sumber: user story BARU US-12, Table B.13 (fitur Sprint 2). Belum ada di SRS LokerLens Appendix B, perlu ditambahkan ke SRS.
Feature: Arsip kasus
  As a Kepala Bidang, I want to menelusuri arsip kasus yang sudah berstatus akhir,
  so that saya dapat melihat kembali dokumen dan riwayat penanganan kasus.

  Scenario: Menampilkan arsip kasus berstatus akhir
    Given I am logged in as "Kepala Bidang"
    When I follow "Arsip Kasus"
    Then I should see "#LKR-8798"
    And I should see "#LKR-8781"
    And I should see "#LKR-8752"
    And I should not see "#LKR-2025-0842"

  Scenario: Melihat detail arsip kasus
    Given I am logged in as "Kepala Bidang"
    And I am on the "/arsip-kasus" page
    When I follow "Buka Detail" in the row for ticket "#LKR-8798"
    Then I should see "Dokumen Surat Resmi"
    And I should see "Status Akhir"

  Scenario: Pencarian arsip tidak menemukan kasus
    Given I am logged in as "Kepala Bidang"
    And I am on the "/arsip-kasus" page
    When I fill in "cari" with "#LKR-0000"
    And I press "Cari"
    Then I should see "Tidak Ada Kasus Sesuai Kriteria Filter"

  Scenario: Peran lain tidak bisa membuka Arsip Kasus
    Given I am logged in as "Analis"
    When I am on the "/arsip-kasus" page
    Then the response status code should be 403
