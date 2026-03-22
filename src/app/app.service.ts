import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http'
import { Observable } from 'rxjs';
import { Enquiry } from './model/enquiry.model';
import { environment } from '../environments/environment';
import { Pin } from './model/pin.model';

@Injectable({
  providedIn: 'root'
})
export class AppService {
  private url: string = environment.apiUrl;
  constructor(private http:HttpClient) { }

  getEqnuiry(): Observable<any> {
    let url = this.url + `enquiry/getEnquiry`;
    return this.http.get(url);
  }

  postEqnuiry(payload: Enquiry): Observable<any> {
    let url = this.url + `enquiry.php`;
    return this.http.post(url, payload);
  }

  getPinData(payload: Pin): Observable<any> {
    let url = this.url + `pindataconfig/getPinData`;
    return this.http.post(url, payload);
  }

  getDropdownData(): Observable<any> {
    let url = this.url + `pindataconfig/getDropdownData`;
    return this.http.get(url);
  }

  addEditPin(payload: any): Observable<any> {
    let url = this.url + `pindataconfig/addEditPin`;
    return this.http.post(url, payload);
  }
}
