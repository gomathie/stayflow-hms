# Security patches carried on top of QloApps 1.7.0

This file lists every change made to **upstream (vendor) files** for security reasons. Each entry names the upstream commit it mirrors, so the patch can be dropped once a QloApps release contains it.

Base: `Qloapps/QloApps` tag `v1.7.0` (commit `f768898c`).

When adopting a new upstream release: for each entry, check whether the release contains the upstream commit. If it does, take the upstream version of those files and delete the entry.

## Backported from upstream `develop`

Applied with `git apply` from `git diff <commit>^1 <commit>`. All applied without conflicts except where noted.

| Issue | Upstream commit | What it changes | Files |
|---|---|---|---|
| CVE-2026-25861: passwords stored as MD5 | `64e9722e7e6a8fda77dd53964d988fb6b5c3d174` (pull request #1689) | Adds `PasswordHashing` (bcrypt). Old MD5 hashes are still accepted and are replaced with bcrypt at the account's next successful login. | `classes/PasswordHashing.php` (new), `classes/Customer.php`, `classes/Employee.php`, `classes/ObjectModel.php`, `classes/Referrer.php`, `classes/Validate.php`, `classes/AdminTab.php`, `classes/controller/AdminController.php`, `classes/webservice/WebserviceSpecificManagementBookings.php`, `controllers/admin/AdminImportController.php`, `controllers/admin/AdminLoginController.php`, `controllers/front/IdentityController.php`, `controllers/front/PasswordController.php`, `install/data/db_structure.sql`, `install/models/install.php`, `install/fixtures/fashion/install.php` |
| CVE-2026-75497, CVE-2026-75498: SQL injection in back office | `123c97c110b7053ea3297d8e58fe95b3c3536560` (pull request #1783) | Validates and casts request parameters before they reach queries. | `classes/Address.php`, `classes/CustomerMessage.php`, `modules/hotelreservationsystem/classes/HotelBranchRefundRules.php`, `…/HotelRoomTypeFeaturePricing.php`, `…/HotelRoomTypeGlobalDemandAdvanceOption.php` |
| CVE-2026-75496: back office upload leading to code execution; CVE-2026-89268: XSS in list filters | `153ec1c8567798bd99155098ecc0a340e38f25bf` (pull request #1801) | Validates booking-document uploads; escapes list filter values. | `admin/themes/default/template/helpers/list/list_header.tpl`, `controllers/admin/AdminOrdersController.php`, `modules/hotelreservationsystem/classes/HotelBookingDocument.php`, `modules/hotelreservationsystem/controllers/admin/AdminBookingDocumentController.php` |
| CVE-2026-93988: path traversal reading arbitrary files | `8015495ca746127920fbcde1f9507c024b26a715` (pull request #1719) | Restricts the email template preview to the mail folders. | `controllers/admin/AdminTranslationsController.php` |
| CVE-2026-92234: XSS in hotel features | `54a3b30e4e5c2d6b224dc8fe55e75db173064b91` (pull request #1796) | Escapes feature names in error messages. | `modules/hotelreservationsystem/controllers/admin/AdminHotelfeaturesController.php` |
| CVE-2026-103587: XSS in back office Book Now search | `1d06fd302a935d68203dbdb341d89482621769d7` (pull request #1884) | Validates the date parameters before they reach the page. | `modules/hotelreservationsystem/controllers/admin/AdminHotelRoomsBookingController.php` |
| CVE-2026-103588, -103589, -103590: XSS in module transplant form, room type editor, length-of-stay fields | `7ed467d911086b190180d7f297e3b15ba31e82d5` (pull request #1899) | Escapes values written into the forms. `configuration.tpl` did not apply cleanly; its five one-line changes were made by hand to match the upstream diff. | `admin/themes/default/template/controllers/modules_positions/form.tpl`, `…/products/booked_room_date_ranges_list.tpl`, `…/products/configuration.tpl`, `…/products/lengthofstay.tpl`, `controllers/admin/AdminModulesPositionsController.php`, `modules/hotelreservationsystem/controllers/admin/AdminHotelRoomsBookingController.php`, `modules/hotelreservationsystem/views/templates/admin/hotel_rooms_booking/helpers/view/view.tpl` |

**Required alongside the password patch:** the `passwd` columns of `qlo_customer`, `qlo_employee` and `qlo_referrer` must be 60 characters wide. On existing installs this is done by the "Schema step" in `docker/entrypoint.sh` before the web server starts. New installs get it from the patched `install/data/db_structure.sql`.

## Our own fixes to upstream files (no upstream commit used)

| Issue | What we changed | Files | Drop when |
|---|---|---|---|
| CVE-2026-25558: stored XSS through SVG upload in the admin file manager | `svg` removed from the allowed upload extensions. | `admin/filemanager/config/config.php` | Upstream ships its own fix. |
| CVE-2025-67325: unauthenticated file upload in hotel reviews | Only the signed-in customer who made the order may post a review. Uploaded files must be real images, at most 5 MB, and are re-encoded and saved as `<n>.jpg`; the name and extension sent by the browser are ignored. | `modules/qlohotelreview/controllers/front/default.php`, `modules/qlohotelreview/classes/QhrHotelReview.php` | Upstream ships its own fix. Compare behaviour before replacing. |

The Hotel Reviews module is additionally **switched off on every deploy** by `docker/setup-modules.php`. To use reviews, remove `qlohotelreview` from the disable list there.

## Open items

Issues that are known but not yet patched are tracked privately, not in this public file.

## Mitigations outside the application code

These live in the Docker layer and are not upstream patches, but they limit the issues above: script execution is denied in every upload folder (`docker/apache.conf`), security headers are set, and uploaded review images are kept on the data volume.
