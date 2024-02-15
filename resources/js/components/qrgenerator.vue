<template>
  <div>
    <input type="file" @change="handleFileUpload">
    <button @click="downloadZip">Download QR Codes as ZIP</button>
    <button @click="chooseBackgroundImage">Choose Background Image</button>
    <button @click="downloadWithBackground">Download with Background</button>

    <div v-if="qrCodes.length > 0" class="flex flex-wrap ">
      <div v-for="(qrCode, index) in qrCodes" :key="index" class="box-border bg-red-600 w-25 w-auto doms mx-1 my-1" :style="{ backgroundImage: `url(${qrCode.backgroundImage})`}" >
        <div class="qr-code" 
        :style="{ left: qrCode.position.x + 'px', top: qrCode.position.y + 'px', transform: `scale(${qrCode.scale || 1})` }"
             @mousedown="handleMouseDown($event, index)"
             @mouseup="handleMouseUp($event, index)"
             @wheel.prevent="handleMouseScroll($event, index)">
          <img :src="qrCode.qrDataURL" alt="QR Code" class="qr-image w-20">
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import QRCode from 'qrcode';
import * as XLSX from 'xlsx';
import JSZip from 'jszip';

export default {
  setup() {
    const qrCodes = ref([]);
    let selectedBackgroundImage = null;
    let initialOffset = { x: 0, y: 0 };

    const handleFileUpload = async (event) => {
      const file = event.target.files[0];
      const fileData = await parseExcel(file);

      const qrCodePromises = fileData.map(async (row) => {
        const textData = JSON.stringify(row[1]);
        const qrDataURL = await generateQRCode(textData);
        return { qrDataURL, position: { x: 0, y: 0 }, scale: 1 }; // Add a scale property
      });

      qrCodes.value = await Promise.all(qrCodePromises);
    };

    const parseExcel = (file) => {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: 'array' });
          const sheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[sheetName];
          const jsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
          resolve(jsonData.slice(1));
        };
        reader.onerror = (error) => {
          reject(error);
        };
        reader.readAsArrayBuffer(file);
      });
    };

    const generateQRCode = async (textData) => {
      try {
        const qrDataURL = await QRCode.toDataURL(textData);
        return qrDataURL;
      } catch (error) {
        console.error('Error generating QR code:', error);
      }
    };

    const chooseBackgroundImage = async () => {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.onchange = async (event) => {
        selectedBackgroundImage = event.target.files[0];
        await addBackgroundImage();
      };
      input.click();
    };

    const addBackgroundImage = async () => {
      for (let i = 0; i < qrCodes.value.length; i++) {
        const qrCode = qrCodes.value[i];
        if (selectedBackgroundImage) {
          qrCode.backgroundImage = await mergeBackground(selectedBackgroundImage);
        }
      }
      // Ensure reactivity by creating a new array reference
      qrCodes.value = [...qrCodes.value];
    };

    const mergeBackground = (backgroundImage) => {
      return new Promise((resolve, reject) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const image = new Image();

        image.onload = () => {
          canvas.width = image.width;
          canvas.height = image.height;
          ctx.drawImage(image, 0, 0);
          resolve(canvas.toDataURL('image/png'));
        };

        image.onerror = (error) => {
          console.error('Error loading background image:', error);
          reject(error);
        };

        image.src = URL.createObjectURL(backgroundImage);
      });
    };

    const downloadZip = async () => {
      const zip = new JSZip();
      qrCodes.value.forEach((qrCode, index) => {
        zip.file(`Invite_${index + 1}.png`, qrCode.qrDataURL.split('base64,')[1], { base64: true });
      });
      const content = await zip.generateAsync({ type: 'blob' });
      const zipFile = new File([content], 'QR_Codes.zip', { type: 'application/zip' });
      const url = URL.createObjectURL(zipFile);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'QR_Codes.zip');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    };

    const downloadWithBackground = async () => {
      const zip = new JSZip();
      for (let i = 0; i < qrCodes.value.length; i++) {
        const qrCode = qrCodes.value[i];
        if (qrCode.backgroundImage) {
          const dataURL = qrCode.backgroundImage.split('base64,')[1];
          zip.file(`Invite_${i + 1}_with_background.png`, dataURL, { base64: true });
        }
      }
      const content = await zip.generateAsync({ type: 'blob' });
      const zipFile = new File([content], 'QR_Codes_with_Background.zip', { type: 'application/zip' });
      const url = URL.createObjectURL(zipFile);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'QR_Codes_with_Background.zip');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    };

    const handleMouseDown = (event, index) => {
      startDrag(event, index);
    };

    const handleMouseUp = (event, index) => {
      endDrag()
    };

    const startDrag = (event, index) => {
      initialOffset.x = event.clientX - qrCodes.value[index].position.x;
      initialOffset.y = event.clientY - qrCodes.value[index].position.y;

      document.addEventListener('mousemove', handleDrag);
      document.addEventListener('mouseup', endDrag);
    };

    const handleDrag = (event) => {
      qrCodes.value.forEach((qrCode, index) => {
        qrCode.position.x = event.clientX - initialOffset.x;
        qrCode.position.y = event.clientY - initialOffset.y;
      });
    };

    const endDrag = () => {
      document.removeEventListener('mousemove', handleDrag);
      document.removeEventListener('mouseup', endDrag);
    };

    const handleMouseScroll = (event, index) => {
  const delta = Math.sign(event.deltaY); // Get the direction of scroll
  const scaleFactor = 1.1; // Adjust this value to control the scale factor
  const qrCode = qrCodes.value[index];
  qrCode.scale = qrCode.scale || 1; // Initialize scale if not set

  if (delta > 0) {
    qrCode.scale /= scaleFactor; // Decrease scale on scroll down
  } else {
    qrCode.scale *= scaleFactor; // Increase scale on scroll up
  }

  // Update the style to reflect the new scale for all QR codes
  qrCodes.value.forEach((code) => {
    code.scale = qrCode.scale;
  });

  // Ensure reactivity by creating a new array reference
  qrCodes.value = [...qrCodes.value];
};

    onMounted(() => {
      const qrImages = document.getElementsByClassName('qr-image');
      for (let i = 0; i < qrImages.length; i++) {
        qrImages[i].style.position = 'absolute';
      }
    });

    return {
      handleFileUpload,
      qrCodes,
      downloadZip,
      chooseBackgroundImage,
      downloadWithBackground,
      handleMouseDown,
      handleMouseUp,
      handleMouseScroll
    };
  }
};
</script>

<style>
.row-container {
  display: flex;
  flex-wrap: wrap;
}

/* .qr-container {
  display: inline-block;
} */

.qr-code {
  position: relative;
  margin: 10px;
  width: 400px;
  height: 400px;
  background-size: cover;
  cursor: pointer;
}

.qr-image {
  position: absolute;
  /* width: 100px;
  height: 100px; */
  pointer-events: none;

}

.doms{
  background-repeat: no-repeat;
  object-fit: cover;
}
</style>
