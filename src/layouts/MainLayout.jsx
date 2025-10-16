import React from "react";
import { Outlet } from "react-router-dom";
import Header from "../components/Header";
import Footer from "../components/Footer";
import "./MainLayout.css"

const MainLayout = () => {
  return (
    <div className="main-layout">
      <Header />
      <main className="content">
        <Outlet /> {/* Home/Login/Register will appear */}
      </main>
      <Footer />
    </div>
  );
};

export default MainLayout;
