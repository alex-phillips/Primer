<?php require_once('Views/templates/partial/common/doc_head.php'); ?>

<nav id="mobile-nav" hidden>
    <ul>
        <li><a href="/">Home</a></li>
        <li><a href="/about/">About us</a></li>
        <li><a href="/contact/">Contact</a></li>
    </ul>
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
        <div class="navbar navbar-default hidden-xs" role="navigation" style="margin-bottom: 0;">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-responsive-collapse">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/">Alex Phillips</a>
            </div>
            <div class="navbar-collapse collapse navbar-responsive-collapse">
                <ul class="nav navbar-nav">
                    <li class="divider hidden-xs"></li>
                    <li>
                        <a href="/posts/">
                            <span class="glyphicon glyphicon-pencil"></span>&nbsp;&nbsp;
                            Posts
                        </a>
                    </li>
                    <li class="divider hidden-xs"></li>
                    <li>
                        <a href="/movies/">
                            <span class="glyphicon glyphicon-film"></span>&nbsp;&nbsp;
                            Movies
                        </a>
                    </li>
                    <li class="divider hidden-xs"></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li class="divider hidden-xs"></li>
                    <?php
                    if ($this->Session->isUserLoggedIn()) {
                        $id_user = $this->Session->read('id');
                        $username = $this->Session->read('username');
                        echo <<<__TEXT__
                            <li class="dropdown">
                                <a class="dropdown-toggle" data-toggle="dropdown" href="#">$username <b class="caret"></b></a>
                                <ul class="dropdown-menu">
                                    <li><a href="/users/view/$id_user">View Account</a></li>
                                    <li><a href="/users/edit/$id_user">Edit Account</a></li>
                                    <li><a href="/posts/add/">New Post</a></li>
                                </ul>
                            </li>
                            <li class="divider hidden-xs"></li>
__TEXT__;

                        echo '<li><a href="/users/logout/">Log Out</a></li>';
                    }
                    else {
                        echo '<li><a href="/users/login/">Log In</a></li>';
                    }
                    ?>
                </ul>
            </div><!-- /.nav-collapse -->
        </div>

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