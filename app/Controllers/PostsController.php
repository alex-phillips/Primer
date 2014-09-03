<?php

class PostsController extends AppController
{
    /* @var $model Post */
    public $name = 'post';

    // Override default config for pagination
    protected $_paginationConfig = array(
        'perPage' => 2,
        'instance' => 'p'
    );

    public function beforeFilter()
    {
        Auth::allow(array(
            'index',
            'view',
            'quotes',
        ));
    }

    public function index()
    {
        $this->view->title = 'Home';
        $params = array(
            'type' => 'post',
        );

        // If not admin, only view publishable posts
        if (!Session::isAdmin()) {
            $params['no_publish'] = 0;
        }

        //get number of total records
        $total = $this->Post->findCount($params);

        //pass number of records to
        $this->view->paginator->set_total($total);

        $this->view->set('posts', $this->Post->find(array(
            'conditions' => array(
                'AND' => $params
            ),
            'order' => array('created DESC'),
            'limit' => $this->view->paginator->get_limit(),
        )));
    }

    public function my_posts()
    {
        $this->view->title = 'My Posts';
        $params = array(
            'type' => 'post',
        );

        // If not admin, only view publishable posts
        if (!Session::isAdmin()) {
            $params['id_user'] = Session::read('Auth.id');
        }

        //get number of total records
        $total = $this->Post->findCount($params);

        //pass number of records to
        $this->view->paginator->set_total($total);

        $this->view->set('posts', $this->Post->find(array(
            'conditions' => array(
                'id_user' => Session::read('Auth.id')
            ),
            'order' => array('created DESC'),
            'limit' => $this->view->paginator->get_limit(),
        )));
        $this->view->render('posts/index');
    }

    public function add()
    {
        $this->view->set('title', 'New Post');
        $this->view->addJS('posts/add');

        if (Request::is('post')) {

            Request::post()->set('data.post.id_user', Session::read('Auth.id'));

            // Set currently signed-in user as creator
            Request::post()->set('data.post.id_user', Session::read('Auth.id'));

            $post = new Post(Request::post()->get('data.post'));
            if ($post->save()) {
                Session::setFlash('Post created successfully', 'success');
                Router::redirect('/posts/');
                return;
            }
        }
        $this->view->render('posts/add');
    }

    public function view($id)
    {
        if (is_numeric($id)) {
            $this->Post = $this->Post->findById($id);
        }
        else {
            $posts = $this->Post->find(array(
                'conditions' => array(
                    'slug' => $id
                )
            ));
            $this->Post = array_shift($posts);
        }

        if ($this->Post->id == '') {
            Session::setFlash('That post does not exist', 'failure');
            Router::redirect('/posts/');
        }
        if (($this->Post->no_publish && !Session::isAdmin()) && $this->Post->id_user !== Session::read('Auth.id')) {
            Session::setFlash('That post does not exist', 'failure');
            Router::redirect('/posts/');
        }

        $this->view->set('post', $this->Post);
        Primer::setValue('rendering_object', $this->Post);

        $this->view->title = $this->Post->title;
        $this->view->set('subtitle', date('F d, Y', strtotime($this->Post->created)));
        $this->view->render('posts/view');
    }

    public function edit($id)
    {
        $this->view->title = 'Edit Post';

        $this->view->addJS('posts/edit');
        $this->Post->set($this->Post->findById($id));

        if ($this->Post->id == '') {
            Session::setFlash('That post does not exist', 'failure');
            Router::redirect('/posts/');
        }

        if ($this->Post->id_user != Session::read('Auth.id') && !Session::isAdmin()) {
            Session::setFlash('You are not authorized to edit that post', 'warning');
            Router::redirect('/posts/');
        }

        // TODO: better way to go about doing this, for security reasons. For ALL models...
        // We are already checking ownership on one of the ID's, but which is best, and they
        // either BOTH need to equal, or make the SQL query on the one we check...
        if (Request::post()->get('data.post.id') && $id != Request::post()->get('data.post.id')) {
            Session::setFlash('Post IDs do not match. Please try again.', 'failure');
            Router::redirect('/posts/edit/' . $id);
        }

        if (Request::is('post')) {
            $this->Post->set(Request::post()->get('data.post'));
            if ($this->Post->save()) {
                Session::setFlash('Post was updated successfully', 'success');
                Router::redirect('/posts/view/' . $id);
            }
            Session::setFlash('There was a problem updating the post', 'failure');
            Router::redirect('/posts/edit/' . $id);
        }

        Primer::setJSValue('post', $this->Post);
        $this->view->set('post', $this->Post);
        $this->view->render('posts/edit');
    }

    public function delete($id = null)
    {
        if (Request::is('post') && Session::isAdmin()) {
            if ($this->Post->deleteById($id)) {
                Session::setFlash('Post has been successfully deleted', 'success');
                Router::redirect('/');
            }
            else {
                Session::setFlash('There was a problem deleting that post', 'failure');
                Router::redirect('/');
            }
        }
        else if (Session::isAdmin()) {
            $this->view->set('post', $this->Post->findById($id));
            $this->view->render('posts/delete');
        }
        else {
            Session::setFlash('You are not authorized to delete posts', 'warning');
            Router::redirect('/');
        }
    }

}