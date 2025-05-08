<?php
// กำหนดโฟลเดอร์เป้าหมายสำหรับการอัพโหลด
$upload_dir = 'rq_id/products-img/';

// ตัวแปรสำหรับเก็บข้อความแจ้งเตือน (สำเร็จ/ผิดพลาด)
$message = '';

// ตัวแปรสำหรับเก็บพาธของสื่อที่จะแสดงผล (วีดีโอหรือรูปภาพ)
$media_to_display = null;
// ตัวแปรสำหรับเก็บประเภทของสื่อที่กำลังแสดง ('video' หรือ 'image')
$media_type = null;

// ตรวจสอบว่ามีการส่งฟอร์มแบบ POST หรือไม่ (นั่นคือมีการกดปุ่มอัพโหลด)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบว่ามีการอัพโหลดไฟล์และไม่มีข้อผิดพลาด
    if (isset($_FILES['mediaFile']) && $_FILES['mediaFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['mediaFile']['tmp_name']; // พาธชั่วคราวของไฟล์
        $fileName = $_FILES['mediaFile']['name'];       // ชื่อไฟล์เดิม
        $fileSize = $_FILES['mediaFile']['size'];       // ขนาดไฟล์
        $fileType = $_FILES['mediaFile']['type'];       // ประเภทไฟล์ MIME (อาจไม่แม่นยำเสมอไป)

        // แยกนามสกุลไฟล์
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // กำหนดนามสกุลไฟล์ที่อนุญาตให้อัพโหลด (เพิ่มรูปภาพ)
        $allowedfileExtensions = array('mp4', 'webm', 'ogg', 'mov', 'avi', 'jpg', 'jpeg', 'png', 'gif', 'webp'); // เพิ่มนามสกุลรูปภาพ

        // สร้างชื่อไฟล์ใหม่แบบสุ่มเพื่อป้องกันชื่อซ้ำและปัญหาอื่นๆ
        // อาจจะเก็บนามสกุลไว้ด้วยก็ได้
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;

        // ตรวจสอบนามสกุลไฟล์
        if (in_array($fileExtension, $allowedfileExtensions)) {

            // กำหนดประเภทของสื่อจากนามสกุล
            $videoExtensions = array('mp4', 'webm', 'ogg', 'mov', 'avi');
            $imageExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');

            if (in_array($fileExtension, $videoExtensions)) {
                $uploaded_media_type = 'video';
            } elseif (in_array($fileExtension, $imageExtensions)) {
                $uploaded_media_type = 'image';
            } else {
                 // ควรจะไม่มาถึงตรงนี้ถ้านามสกุลอยู่ใน allowedfileExtensions
                 $message = 'นามสกุลไฟล์ไม่ถูกต้อง.';
                 $uploaded_media_type = null; // ไม่กำหนดประเภทถ้าไม่รู้นามสกุล
            }


            // ถ้าประเภทไฟล์ถูกต้องตามที่กำหนด
            if ($uploaded_media_type) {
                 // ตรวจสอบและสร้างโฟลเดอร์เป้าหมายหากยังไม่มี
                 if (!is_dir($upload_dir)) {
                     // สร้างโฟลเดอร์แบบ recursive (สร้างโฟลเดอร์ย่อยที่ซ้อนกันได้) และกำหนดสิทธิ์ 0777
                     // หมายเหตุ: 0777 ให้สิทธิ์ทุกคนเขียนได้ ควรปรับเป็นสิทธิ์ที่จำกัดกว่านี้ในสภาพแวดล้อมจริง (เช่น 0755 หรือ 0775)
                     if (!mkdir($upload_dir, 0777, true)) {
                          $message = 'ไม่สามารถสร้างโฟลเดอร์เป้าหมายได้ ตรวจสอบสิทธิ์การเขียนของเซิร์ฟเวอร์.';
                     }
                 }

                 // ถ้าสร้างโฟลเดอร์สำเร็จหรือโฟลเดอร์มีอยู่แล้ว
                 if (is_dir($upload_dir)) {
                      $dest_path = $upload_dir . $newFileName; // กำหนดพาธปลายทางเต็ม

                      // ย้ายไฟล์จากที่เก็บชั่วคราวไปยังโฟลเดอร์เป้าหมาย
                      if (move_uploaded_file($fileTmpPath, $dest_path)) {
                          $message = 'อัพโหลดไฟล์สำเร็จ.';
                          $media_to_display = $dest_path;      // กำหนดให้สื่อที่อัพโหลดมาแสดงผลทันที
                          $media_type = $uploaded_media_type; // กำหนดประเภทสื่อที่แสดงผล
                      } else {
                          $message = 'เกิดข้อผิดพลาดในการย้ายไฟล์ที่อัพโหลด.';
                      }
                 }
            }


        } else {
            $message = 'การอัพโหลดล้มเหลว. นามสกุลไฟล์ที่อนุญาต: ' . implode(', ', $allowedfileExtensions);
        }
    } else {
        // จัดการข้อผิดพลาดในการอัพโหลด
        $message = 'เกิดข้อผิดพลาดในการอัพโหลดไฟล์: ';
        switch ($_FILES['mediaFile']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $message .= 'ไฟล์ที่อัพโหลดมีขนาดใหญ่เกินกว่าที่กำหนด.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $message .= 'ไฟล์ถูกอัพโหลดมาเพียงบางส่วน.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $message .= 'ไม่มีไฟล์ถูกอัพโหลด.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message .= 'ไม่พบโฟลเดอร์ชั่วคราวสำหรับการอัพโหลด.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message .= 'ไม่สามารถเขียนไฟล์ลงดิสก์ได้ ตรวจสอบสิทธิ์การเขียน.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $message .= 'การอัพโหลดถูกหยุดโดยส่วนขยายของ PHP.';
                break;
            default:
                $message .= 'ข้อผิดพลาดที่ไม่ทราบสาเหตุในการอัพโหลด.';
                break;
        }
    }
}

// ถ้ายังไม่มีสื่อที่จะแสดง (เช่น เพิ่งเปิดหน้าครั้งแรก หรืออัพโหลดไม่สำเร็จ)
// ให้ลองค้นหาสื่อล่าสุดในโฟลเดอร์มาแสดงแทน
if (!$media_to_display) {
    // ใช้ glob เพื่อค้นหาไฟล์สื่อทั้งหมดในโฟลเดอร์ (ทั้งวีดีโอและรูปภาพ)
    $files = glob($upload_dir . '*.{mp4,webm,ogg,mov,avi,jpg,jpeg,png,gif,webp}', GLOB_BRACE);

    if ($files) {
        // เรียงลำดับไฟล์ตามเวลาที่แก้ไขล่าสุด (ล่าสุดขึ้นก่อน)
        array_multisort(array_map('filemtime', $files), SORT_DESC, $files);
        // เลือกไฟล์ล่าสุด
        $latest_file = $files[0];

        // กำหนดประเภทสื่อจากนามสกุลของไฟล์ล่าสุด
        $latest_file_extension = strtolower(pathinfo($latest_file, PATHINFO_EXTENSION));
        $videoExtensions = array('mp4', 'webm', 'ogg', 'mov', 'avi');
        $imageExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        if (in_array($latest_file_extension, $videoExtensions)) {
            $media_type = 'video';
            $media_to_display = $latest_file;
        } elseif (in_array($latest_file_extension, $imageExtensions)) {
            $media_type = 'image';
            $media_to_display = $latest_file;
        }
        // ถ้าเป็นนามสกุลอื่นที่ไม่อยู่ในรายการ ก็จะไม่แสดงผลอะไร
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัพโหลดและแสดงสื่อ (วีดีโอ/รูปภาพ)</title>
    <style>
        body {
            background-color: black;
            color: white; /* เพื่อให้ข้อความมองเห็นได้ชัดบนพื้นหลังดำ */
            font-family: sans-serif; /* เพิ่ม font เพื่อให้อ่านง่ายขึ้น */
            margin: 0; /* ลบ margin default */
            padding: 20px; /* เพิ่ม padding รอบๆ */
            text-align: center; /* จัดเนื้อหาตรงกลาง */
            display: flex; /* ใช้ Flexbox จัดเรียงเนื้อหาหลัก */
            flex-direction: column; /* จัดเรียงเนื้อหาหลักตามแนวตั้ง */
            align-items: center; /* จัดเนื้อหาหลักให้อยู่ตรงกลางแนวนอน */
            min-height: 100vh; /* ให้ body มีความสูงขั้นต่ำเท่ากับความสูงของ viewport */
        }

        h1 {
             margin-top: 0; /* ลบ margin ด้านบนของ H1 */
             margin-bottom: 10px; /* เพิ่ม margin ด้านล่างของ H1 */
        }

        /* สไตล์สำหรับสื่อเพื่อให้ปรับขนาดได้ตามหน้าจอ */
        video, img { /* ใช้ selector ร่วมกัน */
            max-width: 100%; /* กว้างไม่เกินความกว้างของพื้นที่ */
            height: auto; /* ปรับความสูงอัตโนมัติ */
            margin-bottom: 20px; /* เพิ่มระยะห่างด้านล่าง */
            border: 1px solid #444; /* เพิ่มขอบ */
            display: block; /* ทำให้วิดีโอ/รูปภาพ เป็น block element */
            margin-left: auto; /* จัดให้อยู่ตรงกลาง */
            margin-right: auto; /* จัดให้อยู่ตรงกลาง */
        }

        /* Container สำหรับหัวข้อและฟอร์มอัพโหลด */
        .control-area {
            display: flex; /* ใช้ Flexbox จัดเรียงลูกๆ (หัวข้อและฟอร์ม) */
            justify-content: center; /* จัดลูกๆ ให้อยู่ตรงกลางแนวนอน */
            align-items: center; /* จัดลูกๆ ให้อยู่ตรงกลางแนวตั้ง */
            flex-wrap: wrap; /* อนุญาตให้ลูกๆ ขึ้นบรรทัดใหม่เมื่อพื้นที่ไม่พอ */
            gap: 30px; /* เพิ่มช่องว่างระหว่างลูกๆ (หัวข้อและฟอร์ม) */
            width: 100%; /* ให้ container กว้างเต็มพื้นที่ */
            max-width: 900px; /* กำหนดความกว้างสูงสุดเพื่อไม่ให้กว้างเกินไป */
            margin-bottom: 20px; /* เพิ่มระยะห่างด้านล่าง */
        }

        .control-area h1 {
             margin: 0; /* ลบ margin ของ H1 ภายใน control-area เพื่อให้ Flexbox จัดการช่องว่าง */
             /* ปรับขนาดหรือสไตล์ของหัวข้อได้ที่นี่ */
             font-size: 1.8em; /* ปรับขนาดตัวอักษร */
        }


        /* สไตล์สำหรับฟอร์มอัพโหลด */
        .control-area form {
            margin: 0; /* ลบ margin ของ Form ภายใน control-area เพื่อให้ Flexbox จัดการช่องว่าง */
            padding: 15px;
            border: 1px solid #333;
            border-radius: 8px;
            background-color: #1a1a1a; /* สีพื้นหลังฟอร์ม */
            display: flex; /* ใช้ Flexbox ภายในฟอร์มเพื่อจัดเรียง Input และ Button */
            align-items: center; /* จัดเรียงลูกๆ ในฟอร์มให้อยู่ตรงกลางแนวตั้ง */
            gap: 10px; /* เพิ่มช่องว่างระหว่าง Input และ Button */
            flex-wrap: wrap; /* ให้ Input และ Button ขึ้นบรรทัดใหม่ได้ถ้าพื้นที่ไม่พอ */
            justify-content: center; /* จัดเรียง Input และ Button ให้อยู่ตรงกลางแนวนอนถ้าขึ้นบรรทัดใหม่ */
        }
         /* สไตล์สำหรับ Input และ Button ภายในฟอร์ม */
        .control-area form input[type="file"],
        .control-area form button[type="submit"] {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
        }

        .control-area form input[type="file"] {
             background-color: #fff;
             color: #333;
             cursor: pointer;
        }
        .control-area form button[type="submit"] {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease; /* เพิ่ม animation */
        }
        .control-area form button[type="submit"]:hover {
            background-color: #0056b3;
        }


        /* สไตล์สำหรับแสดงข้อความแจ้งเตือน */
        .message {
            margin-top: 15px; /* เพิ่มระยะห่างด้านบน */
            padding: 10px;
            border-radius: 5px;
            background-color: #333;
            color: yellow; /* ใช้สีเหลืองสำหรับแจ้งเตือน */
            display: inline-block; /* ทำให้กล่องข้อความไม่กว้างเต็มจอ */
        }

    </style>
</head>
<body>

    <?php if ($media_to_display): ?>
         <?php if ($media_type === 'video'): ?>
            <video id="myMedia" controls autoplay muted> <source src="<?php echo htmlspecialchars($media_to_display); ?>" type="video/<?php echo pathinfo($media_to_display, PATHINFO_EXTENSION); ?>">
                ขอโทษ, เบราว์เซอร์ของคุณไม่รองรับวีดีโอ HTML5
            </video>
			
			
        <?php elseif ($media_type === 'image'): ?>
            <img id="myMedia" src="<?php echo htmlspecialchars($media_to_display); ?>" alt="Uploaded Image"> <?php endif; ?>
    <?php else: ?>
        <p>กรุณาอัพโหลดไฟล์วีดีโอหรือรูปภาพเพื่อเริ่มแสดงผล หรือยังไม่พบไฟล์สื่อในระบบ.</p>
    <?php endif; ?>

    <div class="control-area">
         <h1>ระบบอัพโหลดและแสดงสื่อ</h1> <form action="" method="post" enctype="multipart/form-data">
             เลือกไฟล์วีดีโอหรือรูปภาพ : <input type="file" name="mediaFile" id="mediaFile" accept="video/*,image/*"> <button type="submit" name="submit">อัพโหลด</button>
         </form>
    </div>

    <?php if (!empty($message)): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>


    <?php if ($media_type): // ส่งค่าประเภทสื่อให้ JavaScript ?>
    <script>
        // กำหนดตัวแปรใน JavaScript ตามประเภทสื่อที่ PHP ส่งมา
        var currentMediaType = '<?php echo $media_type; ?>';
		
    </script>
	
    <?php endif; ?>

    <script>
        // ดึง element สื่อ (วีดีโอหรือรูปภาพ) ด้วย ID เดียวกัน
        var mediaElement = document.getElementById("myMedia");

        // ตรวจสอบว่ามี element สื่ออยู่จริงก่อนเพิ่ม event listener/timer
        if (mediaElement) {
            // ตรวจสอบประเภทสื่อจากตัวแปรที่ PHP ส่งมา
            if (typeof currentMediaType !== 'undefined') { // เช็คว่าตัวแปรถูกกำหนดค่ามา
                if (currentMediaType === 'video') {
                    console.log("Video detected, setting onended listener."); // Log เพื่อ Debug
                    // ถ้าเป็นวีดีโอ: เมื่อวีดีโอเล่นจบ
                    mediaElement.onended = function() {
                        console.log("Video ended, refreshing in 5 seconds..."); // Log เพื่อ Debug
                        // หน่วงเวลา 5 วินาทีแล้วค่อยรีเฟรชหน้า
                        setTimeout(function() {
                            location.reload(); // รีเฟรชหน้า
                        }, 5000); // หน่วงเวลา 5000 มิลลิวินาที (5 วินาที)
                    };
                } else if (currentMediaType === 'image') {
                     console.log("Image detected, setting 5 minute timer."); // Log เพื่อ Debug
                     // ถ้าเป็นรูปภาพ: ตั้งเวลาหน่วง 5 นาที แล้วรีเฟรชหน้า
                     // 5 นาที = 5 * 60 วินาที * 1000 มิลลิวินาที = 300000 มิลลิวินาที
                     var refreshTimeout = 300000;
                     console.log("Refreshing page in " + (refreshTimeout / 1000 / 60) + " minutes."); // Log เพื่อ Debug
                     setTimeout(function() {
                         location.reload(); // รีเฟรชหน้า
                     }, refreshTimeout);
                }
            } else {
                 console.log("currentMediaType variable not set."); // Log ถ้าตัวแปรไม่ถูกกำหนด (ไม่น่าจะเกิดขึ้นถ้ามี mediaElement)
            }
        } else {
             console.log("Media element not found, no timer/listener added."); // Log ถ้าไม่พบ element
        }

    </script>

</body>
</html>