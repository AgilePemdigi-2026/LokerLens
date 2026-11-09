# Sumber: SRS LokerLens, Appendix B, Table B.2 (US-01).
Feature: Periksa lowongan kerja

  Background:
    Given I am logged in as "Masyarakat"

  @ml
  Scenario: Pemeriksaan lowongan berhasil dijalankan
    Given I am on the "/periksa-lowongan/" page
    When I fill in "nama_perusahaan" with "PT Maju Bersama Sejahtera"
    And I fill in "posisi_lowongan" with "Staff Administrasi"
    And I fill in "deskripsi_lowongan" with "Dibutuhkan staff administrasi, wajib transfer biaya pendaftaran"
    And I press "Periksa Sekarang"
    Then I should see "Hasil Pemeriksaan"
    And I should see "biaya" in the "#kata-pemicu" element
    And I should see an "#kata-pemicu" element

  Scenario: Gagal memeriksa karena field yang wajib diisi kosong
    Given I am on the "/periksa-lowongan/" page
    When I press "Periksa Sekarang"
    Then I should see "Data belum lengkap, mohon lengkapi terlebih dahulu"
    And I should not see "Hasil Pemeriksaan"

  @javascript
  Scenario: Mereset formulir pemeriksaan
    Given I am on the "/periksa-lowongan/" page
    When I fill in "nama_perusahaan" with "PT Coba Reset"
    And I press "Reset Form"
    Then the "nama_perusahaan" field should not contain "PT Coba Reset"
