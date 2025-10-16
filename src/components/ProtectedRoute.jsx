import React from "react";
import { Navigate } from "react-router-dom";
import { auth } from "../firebaseConfig";

// Checks if the user is logged in
const ProtectedRoute = ({ children }) => {
  const user = auth.currentUser; 
  // If there is no user, redirect to login page
  if (!user) {
    return <Navigate to="/login" replace />;
  }

  // If logged in, load the page
  return children;
};

export default ProtectedRoute;
