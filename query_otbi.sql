-- ============================================================
-- OTBI / BIP DATA MODEL QUERY - Laporan PO
-- ============================================================

WITH receipt_data AS (
    SELECT rt.po_header_id,
           rt.po_line_id,
           rsh.receipt_num,
           rsh.creation_date,
           ROW_NUMBER() OVER (
               PARTITION BY rt.po_header_id, rt.po_line_id
               ORDER BY rsh.creation_date DESC
           ) AS rn
    FROM   rcv_transactions rt
           LEFT JOIN rcv_shipment_headers rsh
               ON rsh.shipment_header_id = rt.shipment_header_id
    WHERE  rt.transaction_type = 'RECEIVE'
      AND  rsh.receipt_num IS NOT NULL
),
invoice_data AS (
    SELECT aida.po_distribution_id,
           aia.invoice_num,
           aia.invoice_date,
           ROW_NUMBER() OVER (
               PARTITION BY aida.po_distribution_id
               ORDER BY aia.invoice_date DESC
           ) AS rn
    FROM   ap_invoice_distributions_all aida
           LEFT JOIN ap_invoices_all aia
               ON aia.invoice_id = aida.invoice_id
    WHERE  aia.invoice_num IS NOT NULL
      AND  aia.invoice_type_lookup_code IN ('STANDARD', 'PREPAYMENT', 'EXPENSE_REPORT')
)
SELECT nomor_po,
       no_requisition,
       tgl_buat_po,
       triwulan,
       tanggal_kontrak,
       nomor_kontrak,
       tanggal_selesai_spmk,
       judul,
       bu,
       jenis_pengadaan,
       jenis_po,
       agreement,
       unit_kerja,
       kode_item,
       nama_item,
       harga_satuan,
       mata_uang,
       qty_ordered,
       jumlah,
       nama_rekanan,
       speksifikasi,
       kategori,
       jenis_item,
       desc_sumber_dana,
       receipt_num,
       receipt_date,
       qty_received,
       qty_delivered,
       invoice_num,
       invoice_date,
       qty_billed,
       jml_billed,
       tipe_po,
       status_po,
       project_number,
       task_number,
       aktifitas,
       po_charge_account
FROM (
    -- ========== STANDARD PO (tanpa agreement/blanket) ==========
    SELECT pha.segment1                                      AS nomor_po,
           pha.type_lookup_code                             AS tipe_po,
           REPLACE(pha.comments, CHR(10), '')               AS judul,
           pha.attribute12                                   AS jenis_po,
           TO_CHAR(pha.creation_date, 'DD-MM-YYYY')         AS tgl_buat_po,
           pha.document_status                              AS status_po,
           pha.attribute1                                    AS nomor_kontrak,
           TO_CHAR(pha.attribute_date1, 'DD-MM-YYYY')       AS tanggal_kontrak,
           TO_CHAR(pha.attribute_date2, 'DD-MM-YYYY')       AS tanggal_selesai_spmk,
           pha.currency_code                                AS mata_uang,
           pla.list_price                                   AS harga_satuan,
           pla.item_description                             AS nama_item,
           pla.quantity                                     AS qty_ordered,
           (pla.list_price * pla.quantity)                  AS jumlah,
           pla.attribute6                                   AS jenis_pengadaan,
           ''                                               AS agreement,
           ''                                               AS judul_agreement,
           ''                                               AS tgl_buat_agreement,
           cattl.description                                AS kategori,
           esib.item_number                                 AS kode_item,
           NVL(line_loc.quantity_received, 0)               AS qty_received,
           NVL(line_loc.quantity_billed, 0)                 AS qty_billed,
           line_loc.input_tax_classification_code,
           haoux.NAME                                       AS bu,
           (SELECT ppf.display_name
              FROM per_person_names_f ppf
             WHERE ppf.person_id = pha.agent_id
               AND TRUNC(SYSDATE) BETWEEN TRUNC(ppf.effective_start_date)
                                      AND TRUNC(ppf.effective_end_date)
               AND ROWNUM = 1)                              AS buyer,
           uomt.description                                 AS nama_satuan,
           hp.party_name                                    AS nama_rekanan,
           REPLACE(esit.long_description, CHR(10), '')      AS speksifikasi,
           eieb.attribute_char1                             AS jenis_item,
           NVL(pda.quantity_delivered, 0)                   AS qty_delivered,
           NVL(pda.amount_billed, 0)                        AS jml_billed,
           NVL(pda.recoverable_tax, 0)                      AS recoverable_tax,
           NVL(pda.nonrecoverable_tax, 0)                   AS nonrecoverable_tax,
           NVL(pda.recoverable_inclusive_tax, 0)            AS recoverable_inclusive_tax,
           NVL(pda.nonrecoverable_inclusive_tax, 0)         AS nonrecoverable_inclusive_tax,
           NVL(pda.tax_exclusive_amount, 0)                 AS tax_exclusive_amount,
           ppa.segment1                                     AS project_number,
           ppa.attribute8                                   AS aktifitas,
           pt.task_name                                     AS task_number,
           gcc.segment1  || '-' || gcc.segment2  || '-' || gcc.segment3  || '-'
           || gcc.segment4  || '-' || gcc.segment5  || '-' || gcc.segment6  || '-'
           || gcc.segment7  || '-' || gcc.segment8  || '-' || gcc.segment9  || '-'
           || gcc.segment10                                 AS po_charge_account,
           ho.NAME                                          AS unit_kerja,
           sq.desc_sumber_dana,
           ppb.attribute6                                   AS triwulan,
           prha.requisition_number                          AS no_requisition,
           rd.receipt_num,
           TO_CHAR(rd.creation_date, 'DD-MM-YYYY')          AS receipt_date,
           id.invoice_num,
           TO_CHAR(id.invoice_date, 'DD-MM-YYYY')           AS invoice_date
    FROM   po_headers_all pha
           INNER JOIN po_lines_all pla
               ON  pla.po_header_id = pha.po_header_id
               AND (pla.line_status NOT IN ('CANCELED') OR pla.line_status IS NULL)
               AND pla.from_header_id IS NULL
           INNER JOIN egp_system_items_b esib
               ON esib.inventory_item_id = pla.item_id
           INNER JOIN po_line_locations_all line_loc
               ON  line_loc.po_line_id = pla.po_line_id
               AND line_loc.ship_to_organization_id = esib.organization_id
           INNER JOIN po_distributions_all pda
               ON  pda.po_header_id = pha.po_header_id
               AND pda.po_line_id   = pla.po_line_id
               AND pda.line_location_id = line_loc.line_location_id
           LEFT JOIN receipt_data rd
               ON  rd.po_header_id = pha.po_header_id
               AND rd.po_line_id   = pla.po_line_id
               AND rd.rn = 1
           LEFT JOIN invoice_data id
               ON  id.po_distribution_id = pda.po_distribution_id
               AND id.rn = 1
           INNER JOIN inv_units_of_measure_b uomb
               ON uomb.uom_code = pla.uom_code
           INNER JOIN inv_units_of_measure_tl uomt
               ON uomt.unit_of_measure_id = uomb.unit_of_measure_id
           INNER JOIN hr_all_organization_units_x haoux
               ON haoux.organization_id = pha.prc_bu_id
           INNER JOIN poz_suppliers pv
               ON pv.vendor_id = pha.vendor_id
           INNER JOIN hz_parties hp
               ON hp.party_id = pv.party_id
           INNER JOIN egp_categories_tl cattl
               ON cattl.category_id = pla.category_id
           INNER JOIN egp_system_items_tl esit
               ON  esit.inventory_item_id = pla.item_id
               AND esit.organization_id   = line_loc.ship_to_organization_id
           INNER JOIN ego_item_eff_b eieb
               ON  eieb.inventory_item_id = pla.item_id
               AND (eieb.attribute_char1 IN ('Aset', 'Non Aset', 'CIP') OR eieb.attribute_char1 IS NULL)
           INNER JOIN gl_code_combinations gcc
               ON gcc.code_combination_id = pda.code_combination_id
           INNER JOIN pjf_projects_all_vl ppa
               ON pda.pjc_project_id = ppa.project_id
           INNER JOIN pjf_tasks_v pt
               ON pda.pjc_task_id = pt.task_id
           INNER JOIN pjf_projects_all_b ppb
               ON ppb.project_id = pda.pjc_project_id
           INNER JOIN hr_operating_units ho
               ON ho.organization_id = ppb.carrying_out_organization_id
           INNER JOIN por_req_distributions_all prda
               ON  prda.distribution_id  = pda.req_distribution_id
               AND prda.code_combination_id = gcc.code_combination_id
           INNER JOIN (
               SELECT ffv.flex_value       AS id_sumber_dana,
                      ffvt.description    AS desc_sumber_dana
               FROM   fnd_flex_values ffv,
                      fnd_flex_value_sets ffvs,
                      fnd_flex_values_tl ffvt
               WHERE  ffv.flex_value_set_id   = ffvs.flex_value_set_id
                 AND  ffvs.flex_value_set_name = 'ITB Sumber Dana'
                 AND  ffv.flex_value_id        = ffvt.flex_value_id
                 AND  ffv.enabled_flag         = 'Y'
                 AND  ffv.end_date_active IS NULL
           ) sq ON sq.id_sumber_dana = gcc.segment3
           INNER JOIN por_requisition_lines_all prla
               ON prla.requisition_line_id = prda.requisition_line_id
           INNER JOIN por_requisition_headers_all prha
               ON prla.requisition_header_id = prha.requisition_header_id
    WHERE  pha.document_status NOT IN ('CANCELED')
      AND  pha.type_lookup_code = 'STANDARD'
      -- Parameter opsional: NULLIF menangani empty string dari BIP
      AND  (TO_CHAR(pha.creation_date, 'YYYY') = :p_thn_buat_po
            OR NULLIF(:p_thn_buat_po, '') IS NULL)

    UNION ALL

    -- ========== BLANKET RELEASE PO (dengan agreement) ==========
    SELECT pha.segment1                                      AS nomor_po,
           pha.type_lookup_code                             AS tipe_po,
           REPLACE(pha.comments, CHR(10), '')               AS judul,
           pha.attribute12                                   AS jenis_po,
           TO_CHAR(pha.creation_date, 'DD-MM-YYYY')         AS tgl_buat_po,
           pha.document_status                              AS status_po,
           pha.attribute1                                    AS nomor_kontrak,
           TO_CHAR(pha.attribute_date1, 'DD-MM-YYYY')       AS tanggal_kontrak,
           TO_CHAR(pha.attribute_date2, 'DD-MM-YYYY')       AS tanggal_selesai_spmk,
           pha.currency_code                                AS mata_uang,
           pla.list_price                                   AS harga_satuan,
           pla.item_description                             AS nama_item,
           pla.quantity                                     AS qty_ordered,
           (pla.list_price * pla.quantity)                  AS jumlah,
           pla.attribute6                                   AS jenis_pengadaan,
           pha2.segment1                                    AS agreement,
           REPLACE(pha2.comments, CHR(10), '')              AS judul_agreement,
           TO_CHAR(pha2.creation_date, 'DD-MM-YYYY')        AS tgl_buat_agreement,
           cattl.description                                AS kategori,
           esib.item_number                                 AS kode_item,
           NVL(line_loc.quantity_received, 0)               AS qty_received,
           NVL(line_loc.quantity_billed, 0)                 AS qty_billed,
           line_loc.input_tax_classification_code,
           haoux.NAME                                       AS bu,
           (SELECT ppf.display_name
              FROM per_person_names_f ppf
             WHERE ppf.person_id = pha.agent_id
               AND TRUNC(SYSDATE) BETWEEN TRUNC(ppf.effective_start_date)
                                      AND TRUNC(ppf.effective_end_date)
               AND ROWNUM = 1)                              AS buyer,
           uomt.description                                 AS nama_satuan,
           hp.party_name                                    AS nama_rekanan,
           REPLACE(esit.long_description, CHR(10), '')      AS speksifikasi,
           eieb.attribute_char1                             AS jenis_item,
           NVL(pda.quantity_delivered, 0)                   AS qty_delivered,
           NVL(pda.amount_billed, 0)                        AS jml_billed,
           NVL(pda.recoverable_tax, 0)                      AS recoverable_tax,
           NVL(pda.nonrecoverable_tax, 0)                   AS nonrecoverable_tax,
           NVL(pda.recoverable_inclusive_tax, 0)            AS recoverable_inclusive_tax,
           NVL(pda.nonrecoverable_inclusive_tax, 0)         AS nonrecoverable_inclusive_tax,
           NVL(pda.tax_exclusive_amount, 0)                 AS tax_exclusive_amount,
           ppa.segment1                                     AS project_number,
           ppa.attribute8                                   AS aktifitas,
           pt.task_name                                     AS task_number,
           gcc.segment1  || '-' || gcc.segment2  || '-' || gcc.segment3  || '-'
           || gcc.segment4  || '-' || gcc.segment5  || '-' || gcc.segment6  || '-'
           || gcc.segment7  || '-' || gcc.segment8  || '-' || gcc.segment9  || '-'
           || gcc.segment10                                 AS po_charge_account,
           ho.NAME                                          AS unit_kerja,
           sq.desc_sumber_dana,
           ppb.attribute6                                   AS triwulan,
           prha.requisition_number                          AS no_requisition,
           rd.receipt_num,
           TO_CHAR(rd.creation_date, 'DD-MM-YYYY')          AS receipt_date,
           id.invoice_num,
           TO_CHAR(id.invoice_date, 'DD-MM-YYYY')           AS invoice_date
    FROM   po_headers_all pha
           INNER JOIN po_lines_all pla
               ON  pla.po_header_id = pha.po_header_id
               AND pla.line_status NOT IN ('CANCELED')
           INNER JOIN po_headers_all pha2
               ON  pha2.po_header_id    = pla.from_header_id
               AND pha2.type_lookup_code = 'BLANKET'
           INNER JOIN egp_categories_tl cattl
               ON cattl.category_id = pla.category_id
           INNER JOIN egp_system_items_b esib
               ON esib.inventory_item_id = pla.item_id
           INNER JOIN po_line_locations_all line_loc
               ON  line_loc.po_line_id = pla.po_line_id
               AND line_loc.ship_to_organization_id = esib.organization_id
           INNER JOIN hr_all_organization_units_x haoux
               ON haoux.organization_id = pha.prc_bu_id
           INNER JOIN inv_units_of_measure_b uomb
               ON uomb.uom_code = pla.uom_code
           INNER JOIN inv_units_of_measure_tl uomt
               ON uomt.unit_of_measure_id = uomb.unit_of_measure_id
           INNER JOIN poz_suppliers pv
               ON pv.vendor_id = pha.vendor_id
           INNER JOIN hz_parties hp
               ON hp.party_id = pv.party_id
           INNER JOIN egp_system_items_tl esit
               ON  esit.inventory_item_id = pla.item_id
               AND esit.organization_id   = line_loc.ship_to_organization_id
           INNER JOIN ego_item_eff_b eieb
               ON  eieb.inventory_item_id = pla.item_id
               AND (eieb.attribute_char1 IN ('Aset', 'Non Aset', 'CIP') OR eieb.attribute_char1 IS NULL)
           INNER JOIN po_distributions_all pda
               ON  pda.po_header_id     = pha.po_header_id
               AND pda.po_line_id       = pla.po_line_id
               AND pda.line_location_id = line_loc.line_location_id
           LEFT JOIN receipt_data rd
               ON  rd.po_header_id = pha.po_header_id
               AND rd.po_line_id   = pla.po_line_id
               AND rd.rn = 1
           LEFT JOIN invoice_data id
               ON  id.po_distribution_id = pda.po_distribution_id
               AND id.rn = 1
           INNER JOIN pjf_projects_all_vl ppa
               ON pda.pjc_project_id = ppa.project_id
           INNER JOIN pjf_tasks_v pt
               ON pda.pjc_task_id = pt.task_id
           INNER JOIN gl_code_combinations gcc
               ON gcc.code_combination_id = pda.code_combination_id
           INNER JOIN pjf_projects_all_b ppb
               ON ppb.project_id = pda.pjc_project_id
           INNER JOIN hr_operating_units ho
               ON ho.organization_id = ppb.carrying_out_organization_id
           INNER JOIN por_req_distributions_all prda
               ON  prda.distribution_id     = pda.req_distribution_id
               AND prda.code_combination_id = gcc.code_combination_id
           INNER JOIN (
               SELECT ffv.flex_value       AS id_sumber_dana,
                      ffvt.description    AS desc_sumber_dana
               FROM   fnd_flex_values ffv,
                      fnd_flex_value_sets ffvs,
                      fnd_flex_values_tl ffvt
               WHERE  ffv.flex_value_set_id   = ffvs.flex_value_set_id
                 AND  ffvs.flex_value_set_name = 'ITB Sumber Dana'
                 AND  ffv.flex_value_id        = ffvt.flex_value_id
                 AND  ffv.enabled_flag         = 'Y'
                 AND  ffv.end_date_active IS NULL
           ) sq ON sq.id_sumber_dana = gcc.segment3
           INNER JOIN por_requisition_lines_all prla
               ON prla.requisition_line_id = prda.requisition_line_id
           INNER JOIN por_requisition_headers_all prha
               ON prla.requisition_header_id = prha.requisition_header_id
    WHERE  pha.document_status NOT IN ('CANCELED')
      AND  (TO_CHAR(pha.creation_date, 'YYYY') = :p_thn_buat_po
            OR NULLIF(:p_thn_buat_po, '') IS NULL)
)
ORDER BY nomor_po, agreement
