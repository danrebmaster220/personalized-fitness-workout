import React, { useEffect, useState } from "react";
import axios from "axios";
import "../../styles/admin/UserManagement.css";

const API_BASE = "/api";

export default function UserManagement() {
  const [users, setUsers] = useState([]);
  const [search, setSearch] = useState("");
  const [filterVerification, setFilterVerification] = useState("all");
  const [filterGender, setFilterGender] = useState("all");
  const [filterFitness, setFilterFitness] = useState("all");

  // Modal states
  const [showModal, setShowModal] = useState(false);
  const [selectedUser, setSelectedUser] = useState(null);

  // Open modal
  const confirmDelete = (user) => {
    setSelectedUser(user);
    setShowModal(true);
  };

  // Confirm delete
  const deleteUserConfirmed = async () => {
    try {
      const res = await axios.delete(
        `${API_BASE}/index.php?route=admin&action=deleteUser&id=${selectedUser.User_ID}`
      );
      if (res.data.success) {
        loadUsers();
      }
    } catch (err) {
      console.error("Delete error:", err);
    }

    setShowModal(false);
    setSelectedUser(null);
  };

  // Fetch users
  const loadUsers = async () => {
    try {
      const res = await axios.get(
        `${API_BASE}/index.php?route=admin&action=getAllUsers`
      );
      if (res.data.success) {
        setUsers(res.data.users);
      }
    } catch (err) {
      console.error("Error fetching users:", err);
    }
  };

  useEffect(() => {
    loadUsers();
  }, []);

  // Filtering logic
  const filteredUsers = users.filter((u) => {
    const matchSearch =
      `${u.FirstName} ${u.LastName}`.toLowerCase().includes(search.toLowerCase()) ||
      u.Email.toLowerCase().includes(search.toLowerCase());

    const matchVerification =
      filterVerification === "all" ||
      (filterVerification === "verified" && u.Is_Verified == 1) ||
      (filterVerification === "unverified" && u.Is_Verified == 0);

    const matchGender = filterGender === "all" || u.Gender === filterGender;

    const matchFitness =
      filterFitness === "all" || u.Fitness_Level === filterFitness;

    return matchSearch && matchVerification && matchGender && matchFitness;
  });

  return (
    <div className="admin-page">
      <h2>User Management</h2>

      {/* Filters */}
      <div className="filters">
        <input
          type="text"
          placeholder="Search users…"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />

        <select
          value={filterVerification}
          onChange={(e) => setFilterVerification(e.target.value)}
        >
          <option value="all">All</option>
          <option value="verified">Verified</option>
          <option value="unverified">Unverified</option>
        </select>

        <select
          value={filterGender}
          onChange={(e) => setFilterGender(e.target.value)}
        >
          <option value="all">Gender: All</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </select>

        <select
          value={filterFitness}
          onChange={(e) => setFilterFitness(e.target.value)}
        >
          <option value="all">Fitness Level: All</option>
          <option value="beginner">Beginner</option>
          <option value="intermediate">Intermediate</option>
          <option value="advanced">Advanced</option>
        </select>
      </div>

      {/* Users Table */}
      <div className="user-table-wrapper">
        <table className="user-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Verification</th>
              <th>Gender</th>
              <th>Fitness</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>

          <tbody>
            {filteredUsers.length === 0 ? (
              <tr>
                <td colSpan="8" style={{ textAlign: "center", padding: "20px" }}>
                  No users found.
                </td>
              </tr>
            ) : (
              filteredUsers.map((u) => (
                <tr key={u.User_ID}>
                  <td>{u.User_ID}</td>
                  <td>
                    {u.FirstName} {u.LastName}
                  </td>
                  <td>{u.Email}</td>
                  <td>{u.Is_Verified ? "Verified" : "Not Verified"}</td>
                  <td>{u.Gender}</td>
                  <td>{u.Fitness_Level}</td>
                  <td>{u.Created_At}</td>

                  <td className="actions-col">
                    {u.Role !== "admin" ? (
                      <button className="delete-btn" onClick={() => confirmDelete(u)}>
                        Delete
                      </button>
                    ) : (
                      <span className="no-action">—</span>
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Modal */}
      {showModal && (
        <div className="modal-overlay">
          <div className="modal-box">
            <h3>Confirm Delete</h3>
            <p>
              Are you sure you want to delete{" "}
              <strong>
                {selectedUser?.FirstName} {selectedUser?.LastName}
              </strong>
              ?
            </p>

            <div className="modal-actions">
              <button className="cancel-btn" onClick={() => setShowModal(false)}>
                Cancel
              </button>

              <button className="confirm-btn" onClick={deleteUserConfirmed}>
                Delete
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
