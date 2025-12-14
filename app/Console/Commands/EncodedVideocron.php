<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use FFMpeg;
use FFMpeg\Format\Video\X264;
use App\Helper;
use App\Models\Media;
use App\Models\Updates;
use App\Models\AdminSettings;
use App\Models\Notifications;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Filters\WatermarkFactory;
class EncodedVideocron extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploadvideos:videouploadercron';


      /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run cron to.';



    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(){
   
      $mediaRecords= Media::where('status','active')->where('encoded','no')->where('type','video')->limit(5)->get();
      if (count($mediaRecords)) {
          foreach ($mediaRecords as $video) {
            $path = 'temp/';
            $videoPathDisk = $path.$video->video;

            if(file_exists($videoPathDisk)){
              $result = cloudinary()->uploadFile($videoPathDisk);
              $securePath = $result->getSecurePath();
              $settings = AdminSettings::first();

              $videoUrl =str_replace("https://res.cloudinary.com/dt2cy0ngx/video/upload/","",$securePath);
              $imageUrl = str_replace($result->getExtension(),"png",$videoUrl);


              // Update name video on Media table
              Media::whereId($video->id)->update([
                  'video' =>  $videoUrl,
                  'encoded' => 'yes',
                  'video_poster' => $imageUrl ?? null
              ]);
    
              // Check if there are other videos that have not been encoded
              $videos = Media::whereUpdatesId($video->updates_id)
                  ->where('video', '<>', '')
                  ->whereEncoded('no')
                  ->get();
    
                  if ($videos->count() == 0) {
    
                    // Update date the post and status
                      Updates::whereId($video->updates_id)->update([
                          'date' => now(),
                          'status' => $settings->auto_approve_post == 'on' ? 'active' : 'pending'
                      ]);
    
                    // Notify to user - destination, author, type, target
                    Notifications::send($video->user_id, $video->user_id, 9, $video->updates_id);
                  }
                  Storage::disk('default')->delete($videoPathDisk);

                  // Move Video File to Storage
                  // $this->moveFileStorage($videoPathDisk);

            }
          }
      }

      Log::info('Cron Job ENED');

    }    

    public function handleOLD()
    {


//      $uploadedFileUrl = cloudinary()->uploadFile($remoteFileUrl)->getSecurePath();

//      dd($uploadedFileUrl);
      
     $mediaRecords= Media::where('status','active')->where('encoded','no')->where('type','video')->limit(2)->get();
     if(count($mediaRecords)){
        foreach ($mediaRecords as $video){
      // Admin Settings
      $settings = AdminSettings::first();
      // Paths
      $disk = 'default';
      $path = 'temp/';
      $videoPathDisk = $path.$video->video;
      if(file_exists($videoPathDisk)){
        $uploadedFileUrl = cloudinary()->uploadFile($videoPathDisk)->getSecurePath();
      }else{
        \Log::error('Video file not found: ' . $videoPathDisk);
        continue;
      }

      $videoPathDiskMp4 = $video->id.Str::random(20).uniqid().now()->timestamp.'-converted.mp4';
      $urlWatermark = ucfirst(Helper::urlToDomain(url('/'))).'/'.$video->user()->username;
      $font = public_path('webfonts/arial.TTF');

      // Create Thumbnail Video
        try {
          $videoPoster = Str::random(20).uniqid().now()->timestamp.'-poster.jpg';

          $ffmpeg = FFMpeg::fromDisk($disk)
          ->open($videoPathDisk)
          ->getFrameFromSeconds(1)
          ->export()
          ->toDisk($disk);
          $ffmpeg->save($path.$videoPoster);

          // Clean
          FFMpeg::cleanupTemporaryFiles();

        } catch (\Exception $e) {
            \Log::error('Error creating video thumbnail: ' . $e->getMessage());
          $videoPoster = null;
        }

      // Create a video format...
      $format = new X264();
      $format->setAudioCodec('aac');
      $format->setVideoCodec('libx264');
      $format->setKiloBitrate(0);

      try {
        // open the uploaded video from the right disk...
        if ($settings->watermark_on_videos == 'on') {

          FFMpeg::fromDisk($disk)
              ->open($videoPathDisk)
                ->addWatermark(function(WatermarkFactory $watermark) {            
                  $watermark->fromDisk('local')
                      ->open('logo.png')
                      ->horizontalAlignment(WatermarkFactory::RIGHT, 25)
                      ->verticalAlignment(WatermarkFactory::BOTTOM, 25)
                     ;
              })
              ->export()
              ->toDisk($disk)
            //   ->resize(640, 480)
              ->inFormat(new \FFMpeg\Format\Video\X264)->save($path.$videoPathDiskMp4);

        } else {
           FFMpeg::fromDisk($disk)
              ->open($videoPathDisk)
              ->addWatermark(function(WatermarkFactory $watermark) {            
                $watermark->fromDisk('local')
                    ->open('logo.png')
                    ->horizontalAlignment(WatermarkFactory::RIGHT, 25)
                    ->verticalAlignment(WatermarkFactory::BOTTOM, 25)
                   ;
            })
              ->export()
              ->toDisk($disk)
              ->inFormat(new \FFMpeg\Format\Video\X264)
            //   ->resize(640, 480)
              ->save($path.$videoPathDiskMp4);
        }
        // Clean
        FFMpeg::cleanupTemporaryFiles();

       // Delete old video
         Storage::disk('default')->delete($videoPathDisk);

          // Update name video on Media table
          Media::whereId($video->id)->update([
              'video' => $videoPathDiskMp4,
              'encoded' => 'yes',
              'video_poster' => $videoPoster ?? null
          ]);

          // Check if there are other videos that have not been encoded
          $videos = Media::whereUpdatesId($video->updates_id)
              ->where('video', '<>', '')
              ->whereEncoded('no')
              ->get();

              if ($videos->count() == 0) {

                // Update date the post and status
                  Updates::whereId($video->updates_id)->update([
                      'date' => now(),
                      'status' => $settings->auto_approve_post == 'on' ? 'active' : 'pending'
                  ]);

                // Notify to user - destination, author, type, target
            		Notifications::send($video->user_id, $video->user_id, 9, $video->updates_id);
              }

              // Move Video File to Storage
              $this->moveFileStorage($videoPathDiskMp4);

              // Move Video Poster to Storage
              if ($videoPoster) {
                $this->moveFileStorage($videoPoster);
              }

      } catch (\Exception $e) {       

        // Update date the post and status
        $post = Updates::whereId($video->updates_id)
          ->whereStatus('encode')
          ->update([
              'date' => now(),
              'status' => $settings->auto_approve_post == 'on' ? 'active' : 'pending'
          ]);

          if ($post) {
            // Notify to user - destination, author, type, target
            Notifications::send($video->user_id, $video->user_id, 9, $video->updates_id);

            // Move Video File to Storage
            $this->moveFileStorage($videoPathDiskMp4);

            // Move Video Poster to Storage
            if ($videoPoster) {
              $this->moveFileStorage($videoPoster);
            }
          }
      }
    }
    }
    }// End Handle

    /**
       * Move file to Storage
       *
       * @return void
       */
    protected function moveFileStorage($file)
    {
      $disk = env('FILESYSTEM_DRIVER');
      $path = config('path.videos');
      $localFile = public_path('temp/'.$file);

     

      // Move the file...
      // Storage::disk($disk)->putFileAs($path, new File($localFile), $file);

      Storage::disk($disk)->put( $path.$file, fopen( $localFile, 'r+'));

      // Delete temp file
      unlink($localFile);

   } // end method moveFileStorage
}
