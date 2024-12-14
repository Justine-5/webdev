<?php
function renderSidebar($currentPage) {
?>

<div class="overlay"></div>
<aside class="sidebar">
  <div class="logo-menu side-logo-menu">
    <button class="menu"></button>
    <h2><a href="home.php" class="logo">Stuby</a></h2>
  </div>

  <div class="section-wrapper">
    <section class="top-sidebar">
      <a href="home.php">
        <div class="sidebar-items <?= $currentPage === 'home' ? 'selected-nav' : '' ?>">
          <img src="icons/home.svg" alt="">
          <p class="aside-labels aside-labels-hidden">Home</p>
        </div>
      </a>
      <a href="decks.php">
        <div class="sidebar-items <?= $currentPage === 'decks' ? 'selected-nav' : '' ?>">
          <img src="icons/decks.svg" alt="">
          <p class="aside-labels aside-labels-hidden">Decks</p>
        </div>
      </a>
      <a href="settings.php">
        <div class="sidebar-items <?= $currentPage === 'settings' ? 'selected-nav' : '' ?>">
          <img src="icons/settings.svg" alt="">
          <p class="aside-labels aside-labels-hidden">Settings</p>
        </div>
      </a>
    </section>

    <section id="btnCloseMenu" class="bottom-sidebar">
      <a href="logout.php">
        <div class="sidebar-items">
          <img src="icons/logout.svg" alt="">
          <p class="aside-labels aside-labels-hidden">Logout</p>
        </div>
      </a>
    </section>
  </div>
</aside>
<?php
}
?>
