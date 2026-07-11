<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VLU Meeting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f6fa; }
        
        /* Sidebar Styling */
        .sidebar { 
            height: 100vh; /* Cố định chiều cao bằng đúng màn hình */
            position: sticky; /* Giữ sidebar đứng yên khi cuộn trang */
            top: 0; 
            background-color: #0d6efd; 
            padding-top: 20px; 
            display: flex; 
            flex-direction: column; 
            overflow-y: auto; /* Cho phép cuộn riêng menu nếu menu quá dài */
        }

        .brand-text { color: #ffffff; font-weight: 800; font-size: 1.8rem; letter-spacing: 1px; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.8); font-weight: 500; padding: 12px 20px; border-radius: 0; margin-bottom: 5px; }
        .sidebar .nav-link i { width: 25px; }
        .sidebar .nav-link:hover { background-color: rgba(1, 0, 77, 0.1); color: #000654; }
        .sidebar .nav-link.active { background-color: rgba(255, 255, 255, 0.2); color: #ffffff; border-right: 4px solid #ffffff; }        
        /* Main Content Styling */
        .main-content { padding: 30px; }
        .top-header { background-color: #ffffff; padding: 15px 30px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .search-bar { background-color: #f5f6fa; border: none; border-radius: 8px; padding: 8px 15px; width: 300px; }
        
        /* Card Styling */
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .icon-box { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        
        /* Colors for Icons */
        .bg-light-blue { background-color: #e6f0ff; color: #0d6efd; }
        .bg-light-green { background-color: #e6ffe6; color: #198754; }
        .bg-light-red { background-color: #ffe5e5; color: #dc3545; }
        .bg-light-yellow { background-color: #fff3e6; color: #fd7e14; }
        
        /* Table Styling */
        .table-card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; }
        .table th { color: #a0a5ba; font-weight: 500; font-size: 0.9rem; border-bottom: 1px solid #eef0f3; padding: 15px; }
        .table td { padding: 15px; vertical-align: middle; color: #2b3674; font-weight: 600; border-bottom: 1px solid #eef0f3; }
        .status-badge { padding: 6px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        
        /* Right Panel Styling */
        .timeline-item { border-left: 2px dashed #eef0f3; padding-left: 20px; position: relative; margin-bottom: 25px; }
        .timeline-item::before { content: ''; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background-color: #d90429; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">