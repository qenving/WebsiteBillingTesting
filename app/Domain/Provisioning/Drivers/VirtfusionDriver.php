<?php

namespace App\Domain\Provisioning\Drivers;

use App\Models\Service;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class VirtfusionDriver extends AbstractDriver
{
    public function key(): string { return 'virtfusion'; }
    public function displayName(): string { return 'Virtfusion'; }
    protected function mock(): bool { return (bool) env('PROVISIONING_MOCK', true); }
    protected function client(): Client
    {
        $base=rtrim((string) config('virtfusion.base_url'),'/'); $key=(string) config('virtfusion.api_key'); if(! $base||!$key) throw new \RuntimeException('Virtfusion not configured: base_url/api_key missing');
        return new Client(['base_uri'=>$base,'timeout'=>30,'headers'=>['Authorization'=>'Bearer '.$key,'Accept'=>'application/json','Content-Type'=>'application/json']]);
    }
    protected function path(string $name,array $vars=[]): string{ $tpl=(string) data_get(config('virtfusion.endpoints'),$name); foreach($vars as $k=>$v){ $tpl=str_replace('{'.$k.'}',$v,$tpl);} return $tpl; }
    protected function request(string $method,string $path,array $options=[]){ $tries=0; $max=3; $delay=200; do{ try{ return $this->client()->request($method,$path,$options);} catch(GuzzleException $e){ $tries++; if($tries>=$max) throw $e; usleep($delay*1000); $delay*=2; } }while($tries<$max); }
    protected function buildCreatePayload(Service $service): array { $plan=(array) data_get($service->meta,'plan',[]); return ['cpu'=>data_get($plan,'cpu'),'ram'=>data_get($plan,'ram'),'storage'=>data_get($plan,'storage'),'bandwidth'=>data_get($plan,'bandwidth'),'template'=>data_get($service->meta,'template'),'hostname'=>'srv-'.$service->id,'notes'=>'Created from billing #'.$service->id]; }

    public function create(Service $service, array $options = []): void
    {
        if($this->mock()){ $meta=$service->meta??[]; $meta['external_id']=$meta['external_id']??('mock-vf-'.$service->id); $meta['ip']=$meta['ip']??('192.0.2.'.($service->id%250+1)); $meta['password']=$meta['password']??bin2hex(random_bytes(4)); $meta['driver']='virtfusion'; $service->external_id=$meta['external_id']; $service->meta=$meta; $service->save(); return; }
        $resp=$this->request('POST',$this->path('create'),['json'=>$this->buildCreatePayload($service)]); $data=json_decode((string)$resp->getBody(),true); $ext=data_get($data,'id')??data_get($data,'data.id'); if(! $ext) throw new \RuntimeException('Virtfusion create: missing external id');
        $meta=$service->meta??[]; $meta['external_id']=$ext; $meta['ip']=data_get($data,'ip')??data_get($data,'data.ip'); $meta['password']=data_get($data,'password')??data_get($data,'data.password'); $meta['driver']='virtfusion'; $service->external_id=(string)$ext; $service->meta=$meta; $service->save();
    }
    public function suspend(Service $service, array $options = []): void { if($this->mock()) return; $this->request('POST',$this->path('suspend',['id'=>$service->external_id])); }
    public function unsuspend(Service $service, array $options = []): void { if($this->mock()) return; $this->request('POST',$this->path('unsuspend',['id'=>$service->external_id])); }
    public function terminate(Service $service, array $options = []): void { if($this->mock()) return; $this->request('DELETE',$this->path('terminate',['id'=>$service->external_id])); }
    public function reboot(Service $service): void { if($this->mock()) return; $this->request('POST',$this->path('reboot',['id'=>$service->external_id])); }
    public function powerOn(Service $service): void { if($this->mock()) return; $this->request('POST',$this->path('power_on',['id'=>$service->external_id])); }
    public function powerOff(Service $service): void { if($this->mock()) return; $this->request('POST',$this->path('power_off',['id'=>$service->external_id])); }
    public function reinstall(Service $service, string $template): void { if($this->mock()){ $meta=$service->meta??[]; $meta['last_reinstall_template']=$template; $service->meta=$meta; $service->save(); return; } $this->request('POST',$this->path('reinstall',['id'=>$service->external_id]),['json'=>['template'=>$template]]); }
    public function resize(Service $service, array $plan): void { if($this->mock()){ $meta=$service->meta??[]; $meta['last_resize']=$plan; $service->meta=$meta; $service->save(); return; } $this->request('POST',$this->path('resize',['id'=>$service->external_id]),['json'=>$plan]); }
    public function snapshot(Service $service, array $options = []): void { if($this->mock()) return; $this->request('POST',$this->path('snapshot',['id'=>$service->external_id]),['json'=>$options]); }
    public function resetPassword(Service $service): void { if($this->mock()){ $meta=$service->meta??[]; $meta['password']=bin2hex(random_bytes(4)); $service->meta=$meta; $service->save(); return; } $resp=$this->request('POST',$this->path('reset_password',['id'=>$service->external_id])); $data=json_decode((string)$resp->getBody(),true); $meta=$service->meta??[]; $meta['password']=data_get($data,'password')??data_get($data,'data.password'); $service->meta=$meta; $service->save(); }
    public function consoleUrl(Service $service): ?string { if($this->mock()) return 'about:blank'; try{ $resp=$this->request('POST',$this->path('console',['id'=>$service->external_id])); $data=json_decode((string)$resp->getBody(),true); return data_get($data,'url')??data_get($data,'data.url'); }catch(\Throwable $e){ return null; } }
}

