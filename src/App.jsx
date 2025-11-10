import { Routes, Route } from "react-router-dom";
import MainLayout from "./layouts/MainLayout";
import Home from "./pages/Home";
import Login from "./pages/user/Login";  // Updated path
import Register from "./pages/user/Register";  // Updated path
import ForgotPassword from "./pages/auth/ForgotPassword"; // Updated path
import ResetPassword from "./pages/auth/ResetPassword";  // Updated path
import EmailVerified from "./pages/auth/EmailVerified";  // Updated path

function App() {
  return (
    <Routes>
      <Route element={<MainLayout />}>
        <Route path="/" element={<Home />} />
      </Route>

      
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route path="/ForgotPassword" element={<ForgotPassword />} />
      <Route path="/reset" element={<ResetPassword />} />
      <Route path="/verify" element={<EmailVerified />} />
    </Routes>
  );
}

export default App;