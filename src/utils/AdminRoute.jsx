import { Navigate } from "react-router-dom";

export default function AdminRoute({ children }) {
  const userData = localStorage.getItem("user");

  let user = null;
  try {
    user = JSON.parse(userData);
  } catch (e) {
    user = null;

    console.error(e);
  }

  if (!user || user.Role !== "admin") {
    return <Navigate to="/login" replace />;
  }

  return children;
}
