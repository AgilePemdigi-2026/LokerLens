# Sumber: SRS LokerLens, Appendix B, Table B.5 (US-04).
Feature: Buat surat teguran/laporan polisi

  Background:
    Given I am logged in as "Kepala Bidang"

  Scenario: Menerbitkan laporan polisi
    Given I am on the letter page for ticket "#LKR-8770"
    When I fill in "kronologi" with "Pelapor menerima surat panggilan interview fiktif atas nama PT Maju Petro Energy"
    And I select "Subdit Siber Ditreskrimsus Polda Metro Jaya / Wilayah Terkait" from "tujuan_kepolisian"
    And I press "Terbitkan Laporan"
    Then I should see "Kasus terdokumentasi otomatis"
    And I should see "Kasus selesai"

  Scenario: Menyimpan surat sebagai draf
    Given I am on the letter page for ticket "#LKR-8770"
    When I fill in "kronologi" with "Draf kronologi sementara"
    And I press "Simpan Draft"
    Then I should see "Draf berhasil disimpan"

  Scenario: Gagal menerbitkan dokumen
    Given I am on the letter page for ticket "#LKR-8770"
    And penerbitan dokumen akan gagal diproses sistem
    When I press "Terbitkan Laporan"
    Then I should see "Gagal menerbitkan dokumen, draf telah disimpan"
    And I should see "Buat Laporan Polisi"
