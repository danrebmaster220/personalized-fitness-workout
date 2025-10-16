// Import the functions you need from the SDKs you need
import { initializeApp } from "firebase/app";
import { getAuth } from "firebase/auth";
import { getFirestore } from "firebase/firestore";
// TODO: Add SDKs for Firebase products that you want to use
// https://firebase.google.com/docs/web/setup#available-libraries

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyAwb0K-s5-nvHwYJO7mdrkSkoBHqKHzSds",
  authDomain: "personalized-fitness-workout.firebaseapp.com",
  projectId: "personalized-fitness-workout",
  storageBucket: "personalized-fitness-workout.firebasestorage.app",
  messagingSenderId: "1090047948671",
  appId: "1:1090047948671:web:6a1218ad5a890224578c4a"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
export const auth = getAuth(app);
export const db = getFirestore(app);
