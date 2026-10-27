# Sumber: SRS LokerLens, Appendix B, Table B.4 (US-03).
Feature: Menindaklanjuti laporan lowongan kerja yang mencurigakan

  Background:
    Given I am logged in as "Kepala Bidang"

  Scenario: Meninjau detail laporan mencurigakan
    Given I am on the "/tindak-lanjut/" page
    When I follow "Lihat Detail"
    Then I should see "Detail Laporan Lowongan Mencurigakan"
    And I should see "Informasi Lowongan"
    And I should see "Ringkasan Laporan"
    And I should see "Terindikasi Anomali Tinggi"

  @javascript
  Scenario: Menandai laporan tidak ditindaklanjuti
    Given I am on the report detail page for ticket "#LKR-2025-0842"
    When I select "Tidak Ditindaklanjuti" from "keputusan"
    And I press "Konfirmasi Keputusan"
    And I press "Ya, Konfirmasi" in the pop-up
    Then I should see "Kasus ditandai tidak dilanjuti dan diarsipkan"

  @javascript
  Scenario: Menindaklanjuti laporan ke pembuatan surat
    Given I am on the report detail page for ticket "#LKR-2025-0842"
    When I select "Tindaklanjuti" from "keputusan"
    And I press "Konfirmasi Keputusan"
    And I press "Ya, Konfirmasi" in the pop-up
    Then I should see "Keputusan Berhasil Disimpan"
    When I follow "Lanjut ke Pembuatan Surat"
    Then the url should match "/buat-surat/\d+$"
    And I should see "Buat Laporan Polisi"
