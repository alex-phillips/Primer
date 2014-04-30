<?php

class PostsController extends AppController
{
    /* @var $model Post */
    public $name = 'post';

    // Override default config for pagination
    public $pagination_config = array(
        'perPage' => 10,
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
        $this->view->render('posts/index');
    }

    public function my_posts()
    {
        $this->view->title = 'My Posts';
        $params = array(
            'type' => 'post',
        );

        // If not admin, only view publishable posts
        if (!Session::isAdmin()) {
            $params['id_user'] = Session::read('id_user');
        }

        //get number of total records
        $total = $this->Post->findCount($params);

        //pass number of records to
        $this->view->paginator->set_total($total);

        $this->view->set('posts', $this->Post->find(array(
            'order' => array('created DESC'),
            'limit' => $this->view->paginator->get_limit(),
        )));
        $this->view->render('posts/index');
    }

    public function add()
    {
        $this->view->set('title', 'New Post');
        View::addJS('posts/add');

        if ($this->request->is('post')) {

            $this->request->data['post']['id_user'] = Session::read('id_user');

            // Only create slug on creation so bookmarks always work in title is edited/changed
            $slug = Inflector::slug($this->request->data['post']['title'], '-');
            // Check to make sure slug doesn't exist, if it does, add timestamp
            $posts = $this->Post->find(array(
                'conditions' => array(
                    'OR' => array(
                        'slug' => $slug,
                        'slug' => $slug . '-' . date('Y-m-d', time()),
                    )
                )
            ));

            // Check if slugs exist, set accordingly
            switch(sizeof($posts)) {
                case 1:
                    $this->request->data['post']['slug'] = $slug . '-' . date('Y-m-d', time());
                    break;
                case 2:
                    $this->request->data['post']['slug'] = $slug . '-' . date('Y-m-d_h-m-s', time());
                    break;
                default:
                    $this->request->data['post']['slug'] = $slug;
                    break;
            }

            $post = new Post($this->request->data['post']);
            if ($post->save()) {
                Session::setFlash('Post created successfully', 'success');
                Session::redirect('/posts/');
                return;
            }
        }
        $this->view->render('posts/add');
    }

    public function view($id_post)
    {
        if (is_numeric($id_post)) {
            $this->Post = $this->Post->findById($id_post);
        }
        else {
            $posts = $this->Post->find(array(
                'conditions' => array(
                    'slug' => $id_post
                )
            ));
            $this->Post = array_shift($posts);
        }

        if ($this->Post->id_post == '') {
            Session::setFlash('That post does not exist', 'failure');
            Session::redirect('/posts/');
        }
        if ($this->Post->no_publish && !Session::isAdmin()) {
            Session::setFlash('That post does not exist', 'failure');
            Session::redirect('/posts/');
        }

        $this->view->set('post', $this->Post);
        Primer::setValue('rendering_object', $this->Post);

        $this->view->title = $this->Post->title;
        $this->view->set('subtitle', date('F d, Y', strtotime($this->Post->created)));
        $this->view->render('posts/view');
    }

    public function edit($id_post)
    {
        $this->view->title = 'Edit Post';

        View::addJS('posts/edit');
        $this->Post->set($this->Post->findById($id_post));

        if ($this->Post->id_post == '') {
            Session::setFlash('That post does not exist', 'failure');
            Session::redirect('/posts/');
        }

        if ($this->Post->id_user != Session::read('id_user') && !Session::isAdmin()) {
            Session::setFlash('You are not authorized to edit that post', 'warning');
            Session::redirect('/posts/');
        }

        // TODO: better way to go about doing this, for security reasons. For ALL models...
        // We are already checking ownership on one of the ID's, but which is best, and they
        // either BOTH need to equal, or make the SQL query on the one we check...
        if (isset($this->request->data['post']['id_post']) && $id_post != $this->request->data['post']['id_post']) {
            Session::setFlash('Post IDs do not match. Please try again.', 'failure');
            Session::redirect('/posts/edit/' . $id_post);
        }

        if ($this->request->is('post')) {
            if (isset($this->request->data['post']['custom_properties'])) {
                $this->request->data['post']['custom_properties'] = json_decode($this->request->data['post']['custom_properties']);
            }
            $this->Post->set($this->request->data['post']);
            if ($this->Post->save()) {
                Session::setFlash('Post was updated successfully', 'success');
                Session::redirect('/posts/view/' . $id_post);
            }
            Session::setFlash('There was a problem updating the post', 'failure');
            Session::redirect('/posts/edit/' . $id_post);
        }

        Primer::setJSValue('post', $this->Post);
        $this->view->set('post', $this->Post);
        $this->view->render('posts/edit');
    }

    public function delete($id_post)
    {
        if ($this->request->is('post') && Session::isAdmin()) {
            if ($this->Post->delete()) {
                Session::setFlash('Post has been successfully deleted', 'success');
                Session::redirect('/');
            }
            else {
                Session::setFlash('There was a problem deleting that post', 'failure');
                Session::redirect('/');
            }
        }
        else if (Session::isAdmin()) {
            $this->view->set('post', $this->Post->findById($id_post));
            $this->view->render('posts/delete');
        }
        else {
            Session::setFlash('You are not authorized to delete posts', 'warning');
            Session::redirect('/');
        }
    }

}