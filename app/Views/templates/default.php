<?php
require_once('Views/templates/partial/common/doc_head.php');
Primer::requireFile('Views/templates/partial/common/navigationRenderer.php');
?>

<nav id="mobile-nav" hidden>
    <?php echo navigationRenderer::buildMobileNav(); ?>
</nav>
<div>
    <div id="mobile-nav-header" class="visible-xs">
        <div class="left-menu"></div>
        <div class="site-name">
            <a href="/"><?php echo SITE_NAME; ?></a>
        </div>
        <div class="right-menu"></div>
    </div>
    <header style="padding-bottom: 20px;">
        <?php echo navigationRenderer::buildDesktopNav(); ?>
        <div class="banner">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <h1><?php echo $this->title ?></h1>
                        <h4>
                            <?php
                            if (isset($this->subtitle)) {
                                echo $this->subtitle;
                            }
                            ?>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- End Nav -->

    <div class="container">
        <!-- Main Page Content and Sidebar -->

        <div class="row">
            <div class="col-sm-12">
                <?php echo $this->flash(); ?>
            </div>
        </div>

        <div class="row">

            <!-- Main Blog Content -->
            <div class="col-sm-9">

                <!--  Flash Messages  -->
                <?php
                $this->getContents();
                if (isset($this->paginator)) {
                    echo $this->paginator->page_links_list();
                }
                ?>

            </div>

            <!-- End Main Content -->


            <!-- Sidebar -->
            <div class="col-sm-3">
                <?php
                require_once('Views/templates/partial/common/sidebar_renderer.php');
                echo sidebar_renderer::render();
                ?>
            </div>

            <!-- End Sidebar -->
        </div>

        <!-- End Main Content and Sidebar -->

        <!-- Footer -->

        <footer class="row">
            <div class="col-lg-12">
                <hr />
                <p>© Alex Phillips 2014</p>
            </div>
        </footer>
    </div>
</div>

<?php require_once('Views/templates/partial/common/doc_tail.php'); ?>