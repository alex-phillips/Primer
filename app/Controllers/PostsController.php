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
        $this->Auth->allow(array(
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
        if (!$this->Session->isAdmin()) {
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
        if (!$this->Session->isAdmin()) {
            $params['id_user'] = $this->Session->read('id');
        }

        //get number of total records
        $total = $this->Post->findCount($params);

        //pass number of records to
        $this->view->paginator->set_total($total);

        $this->view->set('posts', $this->Post->find(array(
            'conditions' => array(
                'id_user' => $this->Session->read('id')
            ),
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

            $this->request->data['post']['id_user'] = $this->Session->read('id_user');

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

            // Set currently signed-in user as creator
            $this->request->data['post']['id_user'] = $this->Session->read('id');

            $post = new Post($this->request->data['post']);
            if ($post->save()) {
                $this->Session->setFlash('Post created successfully', 'success');
                $this->Session->redirect('/posts/');
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
            $this->Session->setFlash('That post does not exist', 'failure');
            $this->Session->redirect('/posts/');
        }
        if (($this->Post->no_publish && !$this->Session->isAdmin()) && $this->Post->id_user !== $this->Session->read('id')) {
            $this->Session->setFlash('That post does not exist', 'failure');
            $this->Session->redirect('/posts/');
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

        View::addJS('posts/edit');
        $this->Post->set($this->Post->findById($id));

        if ($this->Post->id == '') {
            $this->Session->setFlash('That post does not exist', 'failure');
            $this->Session->redirect('/posts/');
        }

        if ($this->Post->id_user != $this->Session->read('id_user') && !$this->Session->isAdmin()) {
            $this->Session->setFlash('You are not authorized to edit that post', 'warning');
            $this->Session->redirect('/posts/');
        }

        // TODO: better way to go about doing this, for security reasons. For ALL models...
        // We are already checking ownership on one of the ID's, but which is best, and they
        // either BOTH need to equal, or make the SQL query on the one we check...
        if (isset($this->request->data['post']['id']) && $id != $this->request->data['post']['id']) {
            $this->Session->setFlash('Post IDs do not match. Please try again.', 'failure');
            $this->Session->redirect('/posts/edit/' . $id);
        }

        if ($this->request->is('post')) {
            if (isset($this->request->data['post']['custom_properties'])) {
                $this->request->data['post']['custom_properties'] = json_decode($this->request->data['post']['custom_properties']);
            }
            $this->Post->set($this->request->data['post']);
            if ($this->Post->save()) {
                $this->Session->setFlash('Post was updated successfully', 'success');
                $this->Session->redirect('/posts/view/' . $id);
            }
            $this->Session->setFlash('There was a problem updating the post', 'failure');
            $this->Session->redirect('/posts/edit/' . $id);
        }

        Primer::setJSValue('post', $this->Post);
        $this->view->set('post', $this->Post);
        $this->view->render('posts/edit');
    }

    public function delete($id)
    {
        if ($this->request->is('post') && $this->Session->isAdmin()) {
            if ($this->Post->deleteById($id)) {
                $this->Session->setFlash('Post has been successfully deleted', 'success');
                $this->Session->redirect('/');
            }
            else {
                $this->Session->setFlash('There was a problem deleting that post', 'failure');
                $this->Session->redirect('/');
            }
        }
        else if ($this->Session->isAdmin()) {
            $this->view->set('post', $this->Post->findById($id));
            $this->view->render('posts/delete');
        }
        else {
            $this->Session->setFlash('You are not authorized to delete posts', 'warning');
            $this->Session->redirect('/');
        }
    }

}