<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\JsonResponse;
use BackendBundle\Entity\User;
use BackendBundle\Entity\Video;

class VideoController extends Controller {

    //Crear nuevo video
    public function newAction(Request $request) {
        $helpers = $this->get("app.helpers");

        $hash = $request->get("authorization", null);
        $authCheck = $helpers->authCheck($hash);

        if ($authCheck == true) {
            $identity = $helpers->authCheck($hash, true);
            $json = $request->get("json", null);


            if ($json != null) {
                $params = json_decode($json);

                $createdAt = new \Datetime('now');
                $updatedAt = new \Datetime('now');
                $imagen = null;
                $video_path = null;

                $user_id = ($identity->sub != null) ? $identity->sub : null;
                $title = (isset($params->title)) ? $params->title : null;
                $desciption = (isset($params->description)) ? $params->description : null;
                $status = (isset($params->status)) ? $params->status : null;

                if ($user_id != null && $title != null) {
                    $em = $this->getDoctrine()->getManager();

                    $user = $em->getRepository("BackendBundle:User")->findOneBy(
                            array(
                                "id" => $user_id
                    ));
                    $video = new Video();
                    $video->setUser($user);
                    $video->setTitle($title);
                    $video->setDescription($desciption);
                    $video->setStatus($status);
                    $video->setCreatedAt($createdAt);
                    $video->setUpdatedAt($updatedAt);

                    $em->persist($video);
                    $em->flush();

                    $video = $em->getRepository("BackendBundle:Video")->findOneBy(
                            array(
                                "user" => $user,
                                "title" => $title,
                                "status" => $status,
                                "createdAt" => $createdAt
                    ));

                    $data = array(
                        "status" => "success",
                        "code" => 200,
                        "data" => $video
                    );
                } else {
                    $data = array(
                        "status" => "error",
                        "code" => 400,
                        "msg" => "Video not created"
                    );
                }
            } else {
                $data = array(
                    "status" => "error",
                    "code" => 400,
                    "msg" => "Video not created, params failed"
                );
            }
        } else {
            $data = array(
                "status" => "error",
                "code" => 400,
                "msg" => "Authorization not valid"
            );
        }

        return $helpers->json($data);
    }

    //Editar video

    public function editAction(Request $request, $id = null) {
        $helpers = $this->get("app.helpers");

        $hash = $request->get("authorization", null);
        $authCheck = $helpers->authCheck($hash);

        if ($authCheck == true) {
            $identity = $helpers->authCheck($hash, true);
            $json = $request->get("json", null);


            if ($json != null) {
                $params = json_decode($json);

                $video_id = $id;
                $createdAt = new \Datetime('now');
                $updatedAt = new \Datetime('now');
                $imagen = null;
                $video_path = null;

                $user_id = ($identity->sub != null) ? $identity->sub : null;
                $title = (isset($params->title)) ? $params->title : null;
                $desciption = (isset($params->description)) ? $params->description : null;
                $status = (isset($params->status)) ? $params->status : null;

                if ($user_id != null && $title != null) {
                    $em = $this->getDoctrine()->getManager();

                    $video = $em->getRepository("BackendBundle:Video")->findOneBy(
                            array(
                                "id" => $video_id
                    ));

                    if (isset($identity->sub) && $identity->sub == $video->getUser()->getId()) {
                        $video->setTitle($title);
                        $video->setDescription($desciption);
                        $video->setStatus($status);
                        $video->setUpdatedAt($updatedAt);

                        $em->persist($video);
                        $em->flush();

                        $data = array(
                            "status" => "success",
                            "code" => 200,
                            "data" => "Video updated success"
                        );
                    } else {
                        $data = array(
                            "status" => "error",
                            "code" => 200,
                            "data" => "Video updated error, you not owner"
                        );
                    }
                } else {
                    $data = array(
                        "status" => "error",
                        "code" => 400,
                        "msg" => "Video updated error"
                    );
                }
            } else {
                $data = array(
                    "status" => "error",
                    "code" => 400,
                    "msg" => "Video not updated, params failed"
                );
            }
        } else {
            $data = array(
                "status" => "error",
                "code" => 400,
                "msg" => "Authorization not valid"
            );
        }

        return $helpers->json($data);
    }

    public function uploadAction(Request $request, $id) {

        $helpers = $this->get("app.helpers");

        $hash = $request->get("authorization", null);
        $authCheck = $helpers->authCheck($hash);

        if ($authCheck == true) {
            $identity = $helpers->authCheck($hash, true);

            $video_id = $id;

            $em = $this->getDoctrine()->getManager();
            $video = $em->getRepository("BackendBundle:Video")->findOneBy(
                    array(
                        "id" => $video_id
            ));

            if ($video_id != null && isset($identity->sub) && $identity->sub == $video->getUser()->getId()) {

                $file = $request->files->get('image', null);
                $file_video = $request->files->get('video', null);

                if ($file != null && !empty($file)) {
                    $ext = $file->guessExtension();

                    if ($ext == "jpeg" || $ext == "jpg" || $ext == "png") {
                        $file_name = time() . "." . $ext;
                        $path_of_file = "uploads/video_images/video_".$video_id;
                        $file->move($path_of_file, $file_name);

                        $video->setImage($file_name);
                        $em->persist($video);
                        $em->flush();

                        $data = array(
                            "status" => "success",
                            "code" => 200,
                            "data" => "image file for video uploaded"
                        );
                    } else {
                        $data = array(
                            "status" => "Error",
                            "code" => 400,
                            "data" => "Format not valid!"
                        );
                    }
                } else {
                    if ($file_video != null && !empty($file_video)) {
                        $ext = $file_video->guessExtension();


                        if ($ext == "mp4" || $ext == "avi") {
                            $file_name = time() . "." . $ext;
                            $path_of_file = "uploads/video_files/video_".$video_id;
                            $file_video->move($path_of_file, $file_name);

                            $video->setVideoPath($file_name);

                            $em->persist($video);
                            $em->flush();

                            $data = array(
                                "status" => "success",
                                "code" => 200,
                                "data" => "Video file uploaded"
                            );
                        } else {
                            $data = array(
                                "status" => "Error",
                                "code" => 400,
                                "data" => "Format not valid!"
                            );
                        }
                    }
                }
            } else {
                $data = array(
                    "status" => "error",
                    "code" => 400,
                    "data" => "Video updated error, you not owner"
                );
            }
        } else {
            $data = array(
                "status" => "error",
                "code" => 400,
                "msg" => "Authorization not valid"
            );
        }

        return $helpers->json($data);
    }
    
    public function videosAction(Request $request){
        $helpers = $this->get("app.helpers");
        
        $em = $this->getDoctrine()->getManager();
        
        $dql = "SELECT v FROM BackendBundle:Video v ORDER BY v.id DESC";
        $query = $em->createQuery($dql);
        
        $page = $request->query->getInt("page",1);
        $paginator = $this->get("knp_paginator");
        $items_per_page = 6;
        
        $pagination = $paginator->paginate($query, $page, $items_per_page);
        $total_items_count = $pagination->getTotalItemCount();
        
        $data = array(
            "status" => "success",
            "total_items_count" => $total_items_count,
            "page_actual" => $page,
            "items_per_page" => $items_per_page,
            "total_pages" => ceil($total_items_count / $items_per_page),
            "data" => $pagination
        );
        
        return $helpers->json($data);
    }
    
    
    public function lastsVideosAction(Request $request){
        $helpers = $this->get("app.helpers");
        $em = $this->getDoctrine()->getManager();
        
        $dql = "SELECT v FROM BackendBundle:Video v ORDER BY v.createdAt DESC";
        $query = $em->createQuery($dql)->setMaxResults(5);
        $videos = $query->getResult();
        
        
        $data = array(
            "status" => 'success',
            "data" => $videos
        );
        
        return $helpers->json($data);
    }
        
    
    public function videoAction(Request $request, $id = null){
        $helpers = $this->get("app.helpers");
        $em = $this->getDoctrine()->getManager(); 
        
        $video = $em->getRepository("BackendBundle:Video")->findOneBy(
                array(
                    "id" => $id
                ));
                
        
                
                if($video){
                    $data = array();
                    $data["status"]="suceess";
                    $data["code"]=200;
                    $data["data"]=$video;
                }else{
                    $data = array(
                    "status" => "error",
                    "code" => 400,
                    "msg" => "Video dont exists"
                );
                }
        
        return $helpers->json($data);
        
    }
    
    
     public function searchAction(Request $request, $search = null){
        $helpers = $this->get("app.helpers");
        
        $em = $this->getDoctrine()->getManager();
        if($search != null){
            $dql = "SELECT v FROM BackendBundle:Video v "
                    . "WHERE v.title LIKE '%$search%' OR "
                    . "v.description LIKE '%$search%' ORDER BY v.id DESC";
        }else{
            $dql = "SELECT v FROM BackendBundle:Video v ORDER BY v.id DESC";
        }
        
        $query = $em->createQuery($dql);
        
        $page = $request->query->getInt("page",1);
        $paginator = $this->get("knp_paginator");
        $items_per_page = 6;
        
        $pagination = $paginator->paginate($query, $page, $items_per_page);
        $total_items_count = $pagination->getTotalItemCount();
        
        $data = array(
            "status" => "success",
            "total_items_count" => $total_items_count,
            "page_actual" => $page,
            "items_per_page" => $items_per_page,
            "total_pages" => ceil($total_items_count / $items_per_page),
            "data" => $pagination
        );
        
        return $helpers->json($data);
    }
    
    
    
    
}
