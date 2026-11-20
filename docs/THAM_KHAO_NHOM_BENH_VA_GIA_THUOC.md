# Căn cứ danh mục bệnh và giá thuốc demo

Cập nhật: 20/11/2026

Tài liệu này ghi lại căn cứ dùng cho danh mục tham khảo của Mediway. Nhóm bệnh và mã bệnh theo danh mục ICD-10 Bộ Y tế đang áp dụng từ ngày 01/07/2026. Giá thuốc là giá tham khảo để chạy demo, không phải bảng giá của Bệnh viện Bạch Mai, giá thanh toán BHYT hay giá bán cố định. Giá bán lẻ và giá trúng thầu thay đổi theo thời điểm, nhà sản xuất, quy cách và cơ sở mua.

## Nhóm bệnh

Thông tư 06/2026/TT-BYT của Bộ Y tế ban hành quy định mã hóa bệnh tật theo ICD-10, có hiệu lực từ 01/07/2026. Danh mục tra cứu ICD-10 chính thức của Cục Quản lý Khám, chữa bệnh ghi rõ đang sử dụng bản ban hành kèm thông tư này. Seeder vì vậy tạo 22 chương ICD-10 theo mã và phạm vi mã, thay cho các nhóm tự đặt như “Nội khoa”, “Tim mạch” hoặc “Hô hấp”. Các nhóm cũ chỉ còn được giữ ở trạng thái ngừng hoạt động nếu hồ sơ lịch sử vẫn đang tham chiếu.

| Mã | Chương ICD-10 | Tên chương trong danh mục | Phạm vi |
|---|---:|---|---|
| ICD01 | I | Bệnh truyền nhiễm và ký sinh trùng | A00–B99 |
| ICD02 | II | U tân sinh | C00–D48 |
| ICD03 | III | Bệnh máu, cơ quan tạo máu và một số rối loạn liên quan đến cơ chế miễn dịch | D50–D89 |
| ICD04 | IV | Bệnh nội tiết, dinh dưỡng và chuyển hóa | E00–E90 |
| ICD05 | V | Rối loạn tâm thần và hành vi | F00–F99 |
| ICD06 | VI | Bệnh hệ thần kinh | G00–G99 |
| ICD07 | VII | Bệnh mắt và phần phụ | H00–H59 |
| ICD08 | VIII | Bệnh tai và xương chũm | H60–H95 |
| ICD09 | IX | Bệnh hệ tuần hoàn | I00–I99 |
| ICD10 | X | Bệnh hệ hô hấp | J00–J99 |
| ICD11 | XI | Bệnh hệ tiêu hóa | K00–K93 |
| ICD12 | XII | Bệnh da và mô dưới da | L00–L99 |
| ICD13 | XIII | Bệnh hệ cơ-xương-khớp và mô liên kết | M00–M99 |
| ICD14 | XIV | Bệnh hệ tiết niệu-sinh dục | N00–N99 |
| ICD15 | XV | Thai nghén, sinh đẻ và hậu sản | O00–O99 |
| ICD16 | XVI | Một số tình trạng bắt nguồn từ thời kỳ chu sinh | P00–P96 |
| ICD17 | XVII | Dị tật bẩm sinh, biến dạng và bất thường nhiễm sắc thể | Q00–Q99 |
| ICD18 | XVIII | Triệu chứng, dấu hiệu và những bất thường về lâm sàng, cận lâm sàng, không phân loại mục khác | R00–R99 |
| ICD19 | XIX | Tổn thương, ngộ độc và một số hậu quả khác do nguyên nhân bên ngoài | S00–T98 |
| ICD20 | XX | Nguyên nhân bên ngoài gây bệnh và tử vong | V01–Y98 |
| ICD21 | XXI | Các yếu tố liên quan đến tình trạng sức khỏe và tiếp cận dịch vụ y tế | Z00–Z99 |
| ICD22 | XXII | Mã cho mục đích đặc biệt | U00–U99 |

Những chẩn đoán thông dụng được đưa lên đầu danh sách để demo nhanh; đây là thứ tự ưu tiên giao diện, không phải khẳng định về tần suất mắc bệnh. Tên chính vẫn là tên ICD-10, tên quen dùng được lưu ở cột tên thay thế.

| Mã | Tên trong danh mục | Tên quen dùng trong giao diện/tìm kiếm |
|---|---|---|
| I10 | Bệnh tăng huyết áp vô căn (nguyên phát) | Tăng huyết áp |
| E11.9 | Bệnh đái tháo đường típ 2, không kèm biến chứng | Đái tháo đường típ 2 |
| J00 | Viêm mũi họng cấp tính [cảm thường] | Cảm lạnh thông thường |
| J02.9 | Viêm họng cấp tính, không xác định | — |
| J18.9 | Viêm phổi, không xác định vi sinh vật | — |
| J45.9 | Hen phế quản, không xác định | — |
| K21.9 | Bệnh trào ngược dạ dày-thực quản không kèm viêm thực quản | GERD |
| K29.7 | Viêm dạ dày, không xác định | — |
| N39.0 | Nhiễm khuẩn đường tiết niệu, không xác định vị trí | — |
| R50.9 | Sốt, không xác định | — |
| J01.9 | Viêm xoang cấp tính, không xác định | — |
| H60.9 | Viêm tai ngoài, không xác định | — |
| I20.9 | Đau thắt ngực, không xác định | — |
| L20.9 | Viêm da cơ địa, không xác định | — |

Mã ICD là phân loại, không phải hướng dẫn tự chẩn đoán hay kê thuốc. Khi dùng thực tế, nhân viên y tế cần chọn mã chi tiết phù hợp với chẩn đoán được xác lập.

## Giá thuốc demo

Giá trong seeder tính theo đơn vị cấp phát cơ bản. Với viên, vỉ demo được quy ước 10 viên; hộp là 100 viên, trừ thuốc phối hợp amoxicillin/clavulanate dùng cấu hình hộp 14 viên (2 vỉ × 7 viên). Với thuốc gói, hộp demo quy ước 20 gói. Các quy cách này chỉ phục vụ dữ liệu demo; không suy ra quy cách của sản phẩm khác nhà sản xuất.

| Mã | Thuốc/hàm lượng | Giá đơn vị | Giá vỉ / chai / tuýp | Giá hộp | Căn cứ tham khảo |
|---|---|---:|---:|---:|---|
| TH001 | Paracetamol 500 mg | 360 đ/viên | 3.600 đ/vỉ | 36.000 đ/100 viên | Giá bán lẻ sản phẩm Quapharco hộp 10 vỉ × 10 viên |
| TH002 | Amoxicillin/clavulanate 875/125 mg | 13.000 đ/viên | 91.000 đ/vỉ | 182.000 đ/14 viên | Mức trúng thầu công khai năm 2026 cho hàm lượng phối hợp tương ứng |
| TH003 | Cetirizine 10 mg | 480 đ/viên | 4.800 đ/vỉ | 48.000 đ/100 viên | Giá bán lẻ Cetirizine Stella 10 mg, hộp 10 vỉ × 10 viên |
| TH004 | Natri clorid 0,9% xịt mũi | 17.000 đ/chai | — | — | Giá bán lẻ Nacofar 60 ml |
| TH005 | Losartan 50 mg | 380 đ/viên | 3.800 đ/vỉ | 38.000 đ/100 viên | Giá bán lẻ Losartan Khapharco 50 mg, hộp 100 viên |
| TH006 | Omeprazole 20 mg | 350 đ/viên | 3.500 đ/vỉ | 35.000 đ/100 viên | Giá bán lẻ Omeprazole Brawn 20 mg, hộp 10 vỉ × 10 viên |
| TH007 | Hỗn dịch kháng acid 10 ml | 2.478 đ/gói | — | 49.560 đ/20 gói | Giá trúng thầu tham khảo của gói kháng acid 10 ml có thành phần tương đương |
| TH008 | Metformin 500 mg | 600 đ/viên | 6.000 đ/vỉ | 60.000 đ/100 viên | Mức trúng thầu công khai năm 2026 cho metformin 500 mg |
| TH009 | Hydrocortisone 1% | 17.979 đ/tuýp | — | — | Mức trúng thầu tham khảo cho kem hydrocortisone 1%, tuýp 10 g |
| TH010 | Acetylcysteine 200 mg | 435 đ/gói | — | 8.700 đ/20 gói | Mức trúng thầu tham khảo cho gói acetylcysteine 200 mg |

Các thuốc mở rộng mã TH9… được định giá theo hoạt chất, hàm lượng và đơn vị dùng từ các kết quả trúng thầu công khai năm 2025–2026; khi không có sản phẩm đóng gói trùng hoàn toàn, đây là **giá ước tính để demo** dựa trên sản phẩm tương đương. Seeder lưu cùng một đơn giá cho từng mã hoạt chất/hàm lượng để chạy thử đồng nhất. Không trình bày các con số này như báo giá bán lẻ hiện hành hay giá bệnh viện đã phê duyệt.

Seeder chỉ điền các ô giá còn trống để việc chạy lại không ghi đè giá được nhân viên quản trị chỉnh sau đó. Ghi chú của từng dòng thuốc trỏ về tài liệu này để giải thích cơ sở giá.

## Nguồn đối chiếu

- [Thông tư 06/2026/TT-BYT trên Cổng văn bản Chính phủ](https://vanban.chinhphu.vn/?classid=1&docid=217536&orggroupid=4&pageid=27160): ngày ban hành 02/04/2026, hiệu lực 01/07/2026.
- [Phụ lục ICD-10 kèm Thông tư 06/2026/TT-BYT](https://datafiles.chinhphu.vn/cpp/files/vbpq/2026/4/06-byt-kem.pdf): bảng chương, phạm vi mã và thuật ngữ mã bệnh.
- [Công cụ tra cứu ICD-10 của Cục Quản lý Khám, chữa bệnh](https://icd10.kcb.vn/): xác nhận phiên bản đang áp dụng và cho tra cứu mã/tên tiếng Việt.
- [Paracetamol 500 mg Quapharco – Nhà thuốc Long Châu](https://nhathuoclongchau.com.vn/thuoc/paracetamol-500mg-quang-binh-10x10-22856.html): sản phẩm 10 vỉ × 10 viên, 360 đ/viên.
- [Cetirizine 10 Stella – Nhà thuốc Long Châu](https://nhathuoclongchau.com.vn/thuoc/cetirizine-10-mg-stella-10-x10.html): sản phẩm 10 vỉ × 10 viên, 480 đ/viên.
- [Losartan 50 mg Khapharco – Nhà thuốc Long Châu](https://nhathuoclongchau.com.vn/thuoc/losartan-50mg-khapharco-10x10-30534.html): hộp 100 viên, 38.000 đ/hộp.
- [Omeprazole 20 mg Brawn – Nhà thuốc Long Châu](https://nhathuoclongchau.com.vn/thuoc/omeprazole-delayed-release-brawn-10x10-18844.html): hộp 100 viên, 35.000 đ/hộp.
- [Nacofar Natri clorid 0,9%, chai 60 ml – Nhà thuốc Long Châu](https://nhathuoclongchau.com.vn/thuoc/nacofar-60ml-pharmadic-15586.html): 17.000 đ/chai.
- [Paracetamol trẻ em Sara, 120 mg/5 ml, 60 ml – Nhà thuốc Long Châu](https://nhathuoclongchau.com.vn/thuoc/sara-strawberry-children-60ml-15153.html): 20.000 đ/chai; dùng làm đối chiếu cho thuốc hỗn dịch trong bộ dữ liệu mở rộng.
- [Tra cứu kết quả trúng thầu thuốc – Cục Quản lý Dược, Bộ Y tế](https://06dichvucong.dav.gov.vn/congbotrungthauthuoc/index): cổng chính thức để tra cứu và đối chiếu giá trúng thầu.
- [Tổng hợp dữ liệu trúng thầu amoxicillin/clavulanate năm 2026](https://voluuhoangtuan.com/thuoc/amoxicilin-acid-clavulanic/): nguồn tổng hợp công khai, không phải cổng nhà nước; mức dùng cho TH002 được đối chiếu với hàm lượng 875/125 mg.
- [Tổng hợp dữ liệu trúng thầu acetylcysteine](https://voluuhoangtuan.com/thuoc/n-acetylcystein/), [hydrocortisone](https://voluuhoangtuan.com/thuoc/hydrocortison/), [metformin](https://voluuhoangtuan.com/thuoc/metformin/), [omeprazole](https://voluuhoangtuan.com/thuoc/omeprazol/), [cetirizine](https://voluuhoangtuan.com/thuoc/cetirizin/), [azithromycin](https://voluuhoangtuan.com/thuoc/azithromycin/), [doxycycline](https://voluuhoangtuan.com/thuoc/doxycyclin/), [cephalexin](https://voluuhoangtuan.com/thuoc/cefalexin/), [paracetamol](https://voluuhoangtuan.com/thuoc/paracetamol/), [losartan](https://voluuhoangtuan.com/thuoc/losartan/), và [một số dịch truyền](https://voluuhoangtuan.com/benh-vien/benh-vien-phoi-thanh-pho-hue/): dữ liệu tổng hợp theo quyết định mua sắm công; cần mở hồ sơ gốc trên DAV khi cần xác minh từng mặt hàng.

Nguồn và mức giá được rà soát ngày 20/11/2026. Trang bán lẻ có thể thay đổi giá; một giá thị trường không thay thế quy trình phê duyệt danh mục và giá của cơ sở khám, chữa bệnh.
