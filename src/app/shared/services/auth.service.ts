import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { JwtHelperService } from '@auth0/angular-jwt';
import { BehaviorSubject } from 'rxjs';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl: string = environment.apiUrl;
  public isAuthenticated = new BehaviorSubject<boolean>(false);
  private tokenKey = 'jwt_token';
  constructor(private http: HttpClient, private jwtHelper: JwtHelperService) {
    this.checkToken();
  }

  login(credentials: any) {
    return this.http.post<any>(`${this.apiUrl}login/authenticate`, credentials);
  }

  saveToken(token: string) {
    localStorage.setItem(this.tokenKey, token);
    this.isAuthenticated.next(true);
  }

  removeToken() {
    localStorage.removeItem(this.tokenKey);
    this.isAuthenticated.next(false);
  }

  getToken() {
    return localStorage.getItem(this.tokenKey);
  }

  checkToken() {
    const token = this.getToken();
    if (token && !this.jwtHelper.isTokenExpired(token)) {
      this.isAuthenticated.next(true);
    } else {
      this.removeToken();
    }
  }

  getUsername(): string | null {
    const token = this.getToken();
    if (token) {
      const decodedToken = this.jwtHelper.decodeToken(token);
      const decodedName = decodedToken['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name']
      return decodedName;
    }
    return null;
  }

  getRole(): string | null {
    const token = this.getToken();
    if (token) {
      const decodedToken = this.jwtHelper.decodeToken(token);
      const decodedRole = decodedToken['http://schemas.microsoft.com/ws/2008/06/identity/claims/role']
      return decodedRole;
    }
    return null;
  }

  isAdmin(): boolean {
    return this.getRole() === "Admin" ? true : false;
  }

  isLoggedIn(): boolean {
    return this.isAuthenticated.value;
  }
}
