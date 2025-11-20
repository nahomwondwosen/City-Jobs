<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>About Us - City Jobs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
<style>
body {
  background-color: #050505;
  color: #e0e0e0;
  font-family: 'Poppins', sans-serif;
  scroll-behavior: smooth;
}
.navbar {
  background: #0b0b0b;
  box-shadow: 0 2px 5px rgba(0,0,0,0.6);
}
.navbar-brand {
  color: #00bfff !important;
  font-weight: 600;
  letter-spacing: 1px;
}
.nav-link {
  color: #b0b0b0 !important;
}
.nav-link:hover {
  color: #00bfff !important;
}
.hero {
  background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
              url('https://images.unsplash.com/photo-1529119368496-2dfda6ec2804?auto=format&fit=crop&w=1920&q=80') center/cover;
  height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #fff;
}
.hero h1 {
  font-size: 3rem;
  font-weight: 700;
  color: #00bfff;
}
.section-title {
  color: #00bfff;
  font-weight: 600;
  margin-bottom: 20px;
  text-align: center;
}
.card {
  background: #111;
  color: #e0e0e0;
  border: 1px solid rgba(0,191,255,0.2);
  border-radius: 12px;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 8px 20px rgba(0,191,255,0.25);
}
footer {
  background: #0b0b0b;
  color: #6caeff;
  padding: 40px 0;
  text-align: center;
  margin-top: 60px;
}
footer p {
  margin: 0;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.html">City Jobs</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
      <li class="nav-item"><a class="nav-link" href="jobs.php">Jobs</a></li>
      
      <li class="nav-item"><a class="nav-link text-info" href="login.php">Login</a></li>
    </ul>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero" data-aos="fade-in">
  <div>
    <h1>About City Jobs</h1>
    <p class="lead">Connecting Ethiopia’s Youth with Opportunity</p>
  </div>
</section>

<!-- Our Story -->
<section class="container py-5">
  <h2 class="section-title" data-aos="fade-right">Our Story</h2>
  <p class="text-center" data-aos="fade-up" style="max-width:800px;margin:auto;">
    City Jobs was born in Jimma with a simple mission. to empower Ethiopian students, freelancers,
    and small businesses by connecting them through a reliable and modern online platform.
    From tech gigs to creative projects, we’re building a bridge between local talent and opportunity.
  </p>
</section>

<!-- Mission and Vision -->
<section class="container py-5">
  <div class="row g-4 text-center">
    <div class="col-md-6" data-aos="fade-up">
      <div class="card p-4 h-100">
        <i class="bi bi-lightbulb-fill text-info fs-1 mb-3"></i>
        <h4 class="fw-bold text-info">Our Mission</h4>
        <p>
          To create a fair, trusted, and accessible freelancing environment where every Ethiopian youth
          can find work, grow skills, and earn income locally and remotely.
        </p>
      </div>
    </div>
    <div class="col-md-6" data-aos="fade-up" data-aos-delay="200">
      <div class="card p-4 h-100">
        <i class="bi bi-globe2 text-info fs-1 mb-3"></i>
        <h4 class="fw-bold text-info">Our Vision</h4>
        <p>
          A connected Ethiopia where talent and opportunity meet empowering a new generation of
          innovators, doers, and creators across every city and region.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Meet the Team -->
<section class="container py-5">
  <h2 class="section-title" data-aos="fade-right">Meet Our Team</h2>
  <div class="row g-4 justify-content-center">
    <div class="col-md-4 col-lg-3" data-aos="zoom-in">
      <div class="card text-center p-3">
        <img src="#" class="rounded-circle mx-auto mb-3" width="100" alt="Team">
        <h5 class="text-info">Nahom Wondwosen</h5>
        <p></p>
      </div>
    </div>
    <div class="col-md-4 col-lg-3" data-aos="zoom-in" data-aos-delay="150">
      <div class="card text-center p-3">
        <img src="#" class="rounded-circle mx-auto mb-3" width="100" alt="Team">
        <h5 class="text-info">Cherinet Jihad</h5>
        <p></p>
      </div>
    </div>
    <div class="col-md-4 col-lg-3" data-aos="zoom-in" data-aos-delay="300">
      <div class="card text-center p-3">
        <img src="#" class="rounded-circle mx-auto mb-3" width="100" alt="Team">
        <h5 class="text-info">Nejat Kedir</h5>
        <p></p>
      </div>
    </div>
  </div>
</section>
<section class="container py-5">
  
  <div class="row g-4 justify-content-center">
    <div class="col-md-4 col-lg-3" data-aos="zoom-in">
      <div class="card text-center p-3">
        <img src="file/photo_2025-10-24_04-03-09.jpg" class="rounded-circle mx-auto mb-3" width="100" alt="Team">
        <h5 class="text-info">Sisay Alemayehu</h5>
        <p></p>
      </div>
    </div>
    <div class="col-md-4 col-lg-3" data-aos="zoom-in" data-aos-delay="150">
      <div class="card text-center p-3">
        <img src="#" class="rounded-circle mx-auto mb-3" width="100" alt="Team">
        <h5 class="text-info">Menase Bamlaku</h5>
        <p></p>
      </div>
    </div>
   
  </div>
</section>

<!-- Footer -->
<footer data-aos="fade-up">
  <p>© 2025 City Jobs | Empowering Jimma & Ethiopian Freelancers</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ duration: 900, offset: 120, easing: 'ease-in-out' });
</script>
</body>
</html>
