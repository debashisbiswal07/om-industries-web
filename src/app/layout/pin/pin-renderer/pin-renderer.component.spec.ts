import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PinRendererComponent } from './pin-renderer.component';

describe('PinRendererComponent', () => {
  let component: PinRendererComponent;
  let fixture: ComponentFixture<PinRendererComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PinRendererComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PinRendererComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
