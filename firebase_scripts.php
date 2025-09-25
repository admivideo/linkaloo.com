<script type="module">
  // Import the functions you need from the SDKs you need
  import { initializeApp } from "https://www.gstatic.com/firebasejs/12.3.0/firebase-app.js";
  import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.3.0/firebase-analytics.js";
  // TODO: Add SDKs for Firebase products that you want to use
  // https://firebase.google.com/docs/web/setup#available-libraries

  // Your web app's Firebase configuration
  // For Firebase JS SDK v7.20.0 and later, measurementId is optional
  const firebaseConfig = {
    apiKey: "AIzaSyAB8qBGmzwy15sqxyLevfGYU5GwAZtTAR8",
    authDomain: "linkaloo-6ca2f.firebaseapp.com",
    projectId: "linkaloo-6ca2f",
    storageBucket: "linkaloo-6ca2f.firebasestorage.app",
    messagingSenderId: "170566271159",
    appId: "1:170566271159:web:d050991bb5d1b677410d32",
    measurementId: "G-1KZQW4DWJP"
  };

  // Initialize Firebase
  const app = initializeApp(firebaseConfig);
  const analytics = getAnalytics(app);
</script>
